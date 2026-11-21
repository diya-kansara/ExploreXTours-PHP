<?php
/**
 * Booking domain logic: validation, server-side pricing, references,
 * ownership checks, status rules and payment simulation.
 */
declare(strict_types=1);

const BOOKING_STATUSES = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];
const PAYMENT_METHODS  = ['Demo Card', 'Demo UPI', 'Demo Net Banking'];

function package_by_id(int $id, bool $onlyActive = true): ?array
{
    $sql = PACKAGE_SELECT . ' WHERE p.id = ?' . ($onlyActive ? ' AND p.is_active = 1' : '');
    return db_one($sql, 'i', [$id]);
}

function package_full_by_id(int $id, bool $onlyActive = true): ?array
{
    $row = db_one('SELECT * FROM packages WHERE id = ?' . ($onlyActive ? ' AND is_active = 1' : ''), 'i', [$id]);
    return $row;
}

/** Total is ALWAYS computed here from the database price - never trusted from the browser. */
function calculate_total(float $unitPrice, int $guests): float
{
    return round($unitPrice * $guests, 2);
}

function return_date_for(string $travelDate, int $durationDays): string
{
    $d = new DateTime($travelDate);
    $d->modify('+' . max(0, $durationDays - 1) . ' days');
    return $d->format('Y-m-d');
}

function earliest_travel_date(): string
{
    return date('Y-m-d', strtotime('+' . (int) config('min_days_before_travel', 1) . ' days'));
}

function latest_travel_date(): string
{
    return date('Y-m-d', strtotime('+2 years'));
}

/**
 * Validates booking form input against the package. Returns [clean data, errors].
 */
function validate_booking_input(array $pkg, array $in): array
{
    $errors = [];
    $maxGuests = min((int) config('max_guests_per_booking', 12), (int) $pkg['max_guests']);

    $date = trim((string) ($in['travel_date'] ?? ''));
    if (!valid_date($date)) {
        $errors['travel_date'] = 'Please choose a valid travel date.';
    } elseif ($date < earliest_travel_date()) {
        $errors['travel_date'] = 'Travel date must be on or after ' . fmt_date(earliest_travel_date()) . '.';
    } elseif ($date > latest_travel_date()) {
        $errors['travel_date'] = 'Please choose a date within the next two years.';
    }

    $guests = (is_string($in['guests'] ?? null) && preg_match('/^\d{1,3}$/', $in['guests'])) ? (int) $in['guests'] : 0;
    if ($guests < 1 || $guests > $maxGuests) {
        $errors['guests'] = 'Guests must be between 1 and ' . $maxGuests . ' for this package.';
    }

    $name  = mb_substr(trim((string) ($in['contact_name'] ?? '')), 0, 100);
    $email = mb_substr(trim((string) ($in['contact_email'] ?? '')), 0, 150);
    $phone = mb_substr(trim((string) ($in['contact_phone'] ?? '')), 0, 20);
    if (mb_strlen($name) < 2)                       $errors['contact_name']  = 'Please enter the lead traveller\'s full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['contact_email'] = 'Please enter a valid email address.';
    if (!valid_phone($phone))                       $errors['contact_phone'] = 'Please enter a valid phone number (8-15 digits).';

    $requests = mb_substr(trim((string) ($in['special_requests'] ?? '')), 0, 1000);

    // traveller list: traveller_name[0..n-1], traveller_age[0..n-1]; index 0 is the lead (= contact name)
    $names = is_array($in['traveller_name'] ?? null) ? $in['traveller_name'] : [];
    $ages  = is_array($in['traveller_age'] ?? null) ? $in['traveller_age'] : [];
    $travellers = [];
    for ($i = 0; $i < $guests; $i++) {
        $tn = $i === 0 ? $name : mb_substr(trim((string) ($names[$i] ?? '')), 0, 100);
        $ta = (isset($ages[$i]) && is_string($ages[$i]) && preg_match('/^\d{1,3}$/', $ages[$i])) ? (int) $ages[$i] : null;
        if ($i > 0 && mb_strlen($tn) < 2) {
            $errors['traveller_' . $i] = 'Please enter the name of traveller ' . ($i + 1) . '.';
        }
        if ($ta !== null && ($ta < 0 || $ta > 110)) {
            $errors['traveller_' . $i] = 'Please enter a realistic age for traveller ' . ($i + 1) . '.';
        }
        $travellers[] = ['name' => $tn, 'age' => $ta, 'is_lead' => $i === 0 ? 1 : 0];
    }

    $clean = [
        'package_id'       => (int) $pkg['id'],
        'travel_date'      => $date,
        'guests'           => $guests,
        'contact_name'     => $name,
        'contact_email'    => $email,
        'contact_phone'    => $phone,
        'special_requests' => $requests,
        'travellers'       => $travellers,
    ];
    return [$clean, $errors];
}

function new_booking_ref(): string
{
    return 'EX' . date('ymd') . '-' . random_code(6);
}

/**
 * Creates the booking (status Pending) inside a transaction.
 * Returns the booking id. Throws mysqli_sql_exception with errno 1062 on a duplicate token.
 */
function create_booking(int $userId, array $pkg, array $data, string $submitToken): int
{
    $unit  = (float) $pkg['price'];
    $total = calculate_total($unit, (int) $data['guests']);
    $ret   = return_date_for($data['travel_date'], (int) $pkg['duration_days']);

    return db_transaction(function () use ($userId, $pkg, $data, $submitToken, $unit, $total, $ret) {
        $id = 0;
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $id = db_insert(
                    'INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests,
                                           travel_date, return_date, unit_price, total_amount, special_requests, status, submit_token)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,\'Pending\',?)',
                    'siisssissddss',
                    [new_booking_ref(), $userId, (int) $pkg['id'], $data['contact_name'], $data['contact_email'], $data['contact_phone'],
                     $data['guests'], $data['travel_date'], $ret, $unit, $total, $data['special_requests'], $submitToken]
                );
                break;
            } catch (mysqli_sql_exception $e) {
                // 1062 on booking_ref -> regenerate; on submit_token -> real duplicate submission
                if ((int) $e->getCode() === 1062 && !str_contains($e->getMessage(), 'uq_bookings_token')) {
                    continue;
                }
                throw $e;
            }
        }
        foreach ($data['travellers'] as $t) {
            db_exec('INSERT INTO booking_travellers (booking_id, full_name, age, is_lead) VALUES (?,?,?,?)',
                'isii', [$id, $t['name'], $t['age'], $t['is_lead']]);
        }
        return $id;
    });
}

/** Ownership-checked fetch for customers: returns null if the booking is not theirs. */
function user_booking(int $bookingId, int $userId): ?array
{
    return db_one(
        'SELECT b.*, p.title AS package_title, p.location AS package_location, p.image AS package_image, p.duration AS package_duration
           FROM bookings b JOIN packages p ON p.id = b.package_id
          WHERE b.id = ? AND b.user_id = ?',
        'ii', [$bookingId, $userId]
    );
}

function user_booking_by_ref(string $ref, int $userId): ?array
{
    return db_one(
        'SELECT b.*, p.title AS package_title, p.location AS package_location, p.image AS package_image, p.duration AS package_duration
           FROM bookings b JOIN packages p ON p.id = b.package_id
          WHERE b.booking_ref = ? AND b.user_id = ?',
        'si', [$ref, $userId]
    );
}

function booking_paid(int $bookingId): bool
{
    return (int) db_value("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'Success'", 'i', [$bookingId]) > 0;
}

function booking_payment_label(int $bookingId): string
{
    if (booking_paid($bookingId)) {
        return 'Paid (demo)';
    }
    $failed = (int) db_value("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'Failed'", 'i', [$bookingId]);
    return $failed > 0 ? 'Payment failed' : 'Unpaid';
}

/** Customer may request cancellation only for Pending/Confirmed trips that have not started. */
function can_request_cancellation(array $b): bool
{
    return in_array($b['status'], ['Pending', 'Confirmed'], true)
        && !(int) $b['cancellation_requested']
        && $b['travel_date'] >= date('Y-m-d');
}

function can_review_booking(array $b): bool
{
    return $b['status'] === 'Completed'
        && db_value('SELECT COUNT(*) FROM reviews WHERE booking_id = ?', 'i', [(int) $b['id']]) == 0;
}

/** Allowed admin status transitions. */
function allowed_status_changes(string $current): array
{
    return match ($current) {
        'Pending'   => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Completed', 'Cancelled'],
        default     => [],       // Cancelled / Completed are final
    };
}

// ---- simulated payments ----------------------------------------------------
function transaction_ref(): string
{
    return 'TXN-DEMO-' . strtoupper(bin2hex(random_bytes(5)));
}

/**
 * Records a SIMULATED payment. No card number, expiry or CVV is ever read, sent or stored.
 * $outcome: 'success' | 'failure'
 */
function record_simulated_payment(array $booking, string $method, string $outcome): array
{
    if (!in_array($method, PAYMENT_METHODS, true)) {
        $method = 'Demo Card';
    }
    $status = $outcome === 'success' ? 'Success' : 'Failed';
    $reason = $status === 'Failed' ? 'Simulated failure: the demo gateway declined this payment.' : null;
    $ref    = transaction_ref();
    db_insert(
        'INSERT INTO payments (booking_id, transaction_ref, amount, method, status, failure_reason) VALUES (?,?,?,?,?,?)',
        'isdsss', [(int) $booking['id'], $ref, (float) $booking['total_amount'], $method, $status, $reason]
    );
    return ['status' => $status, 'ref' => $ref];
}
