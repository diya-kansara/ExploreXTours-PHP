<?php
/**
 * Booking workflow, steps 1-2 on one URL:
 *   (1) trip details form  ->  (2) review summary  ->  confirm  ->  checkout.php
 * Price is calculated on the server from the database price. Hidden-field prices are never used.
 */
require __DIR__ . '/includes/bootstrap.php';

$pid = get_int('package_id') ?: post_int('package_id');
$user = current_user();
if (!$user) {
    flash('info', 'Please log in or create an account to book this tour.');
    redirect('user/login.php?next=' . rawurlencode('book.php?package_id=' . $pid));
}
$pkg = $pid > 0 ? package_by_id($pid) : null;
$full = $pkg ? package_full_by_id($pid) : null;
if (!$pkg) {
    abort(404, 'That tour package does not exist or is no longer available.');
}
$maxGuests = min((int) config('max_guests_per_booking', 12), (int) $full['max_guests']);

$errors = [];
$step = 1;
$data = [
    'travel_date' => get_str('date', 10), 'guests' => '2',
    'contact_name' => $user['name'], 'contact_email' => $user['email'], 'contact_phone' => (string) $user['phone'],
    'special_requests' => '', 'traveller_name' => [], 'traveller_age' => [],
];

if (is_post()) {
    csrf_verify();
    $action = post_str('action', 10);

    if ($action === 'review' || $action === 'edit') {
        // keep what the user typed so the form can be re-displayed
        foreach (['travel_date', 'guests', 'contact_name', 'contact_email', 'contact_phone', 'special_requests'] as $k) {
            $data[$k] = $_POST[$k] ?? '';
            $data[$k] = is_string($data[$k]) ? $data[$k] : '';
        }
        $data['traveller_name'] = is_array($_POST['traveller_name'] ?? null) ? $_POST['traveller_name'] : [];
        $data['traveller_age']  = is_array($_POST['traveller_age'] ?? null) ? $_POST['traveller_age'] : [];

        if ($action === 'review') {
            [$clean, $errors] = validate_booking_input($pkg, $data);
            if (!$errors) {
                $token = bin2hex(random_bytes(16));
                $_SESSION['booking_draft'] = ['package_id' => $pid, 'token' => $token, 'data' => $clean];
                $step = 2;
            }
        }
    } elseif ($action === 'confirm') {
        $token = post_str('token', 32);
        $draft = $_SESSION['booking_draft'] ?? null;
        if (!$draft || $draft['package_id'] !== $pid || !hash_equals($draft['token'], $token)) {
            // double-click / back-button resubmission: if this token already produced a booking, go there
            $existing = db_one('SELECT booking_ref FROM bookings WHERE submit_token = ? AND user_id = ?', 'si', [$token, (int) $user['id']]);
            if ($existing) {
                redirect('checkout.php?ref=' . rawurlencode($existing['booking_ref']));
            }
            flash('error', 'Your booking session expired. Please review your details again.');
            redirect('book.php?package_id=' . $pid);
        }
        // re-validate everything server-side right before writing (date may have rolled over, etc.)
        [$clean, $errors] = validate_booking_input($pkg, array_merge($draft['data'], [
            'traveller_name' => array_column($draft['data']['travellers'], 'name'),
            'traveller_age'  => array_map(fn($t) => $t['age'] === null ? '' : (string) $t['age'], $draft['data']['travellers']),
            'guests' => (string) $draft['data']['guests'],
        ]));
        if ($errors) {
            unset($_SESSION['booking_draft']);
            flash('error', 'Some details are no longer valid. Please check them again.');
            redirect('book.php?package_id=' . $pid);
        }
        try {
            $bookingId = create_booking((int) $user['id'], $pkg, $clean, $draft['token']);
            unset($_SESSION['booking_draft']);
            $ref = (string) db_value('SELECT booking_ref FROM bookings WHERE id = ? AND user_id = ?', 'ii', [$bookingId, (int) $user['id']]);
            redirect('checkout.php?ref=' . rawurlencode($ref));
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() !== 1062) { throw $e; }
            $existing = db_one('SELECT booking_ref FROM bookings WHERE submit_token = ? AND user_id = ?', 'si', [$draft['token'], (int) $user['id']]);
            if ($existing) {
                redirect('checkout.php?ref=' . rawurlencode($existing['booking_ref']));
            }
            throw $e;
        }
    }
}

// ---------------------------------------------------------------------------
$draft = $_SESSION['booking_draft'] ?? null;
page_header('Book ' . $pkg['title'], ['active' => 'packages.php']);
?>
<div class="container" style="padding-top:36px">
  <p class="crumbs" style="margin-top:0"><a href="<?= e(url('package_details.php?id=' . $pid)) ?>">← Back to tour</a></p>
  <h1 style="font-size:2.2rem">Book “<?= e($pkg['title']) ?>”</h1>
  <?php booking_steps($step); ?>
  <div class="checkout">
  <?php if ($step === 2 && $draft): $d = $draft['data']; $ret = return_date_for($d['travel_date'], (int) $full['duration_days']); $total = calculate_total((float) $pkg['price'], (int) $d['guests']); ?>
    <div>
      <div class="panel"><h2>Review your booking</h2>
        <dl class="kv">
          <dt>Tour</dt><dd><?= e($pkg['title']) ?> · <?= e($pkg['location']) ?></dd>
          <dt>Travel dates</dt><dd><?= e(fmt_date($d['travel_date'], 'D, d M Y')) ?> → <?= e(fmt_date($ret, 'D, d M Y')) ?> (<?= e($pkg['duration']) ?>)</dd>
          <dt>Guests</dt><dd><?= (int) $d['guests'] ?></dd>
          <dt>Lead traveller</dt><dd><?= e($d['contact_name']) ?> · <?= e($d['contact_email']) ?> · <?= e($d['contact_phone']) ?></dd>
          <dt>Travellers</dt><dd><?php foreach ($d['travellers'] as $i => $t): ?><?= e($t['name']) ?><?= $t['age'] !== null ? ' (' . (int) $t['age'] . ')' : '' ?><?= $i < count($d['travellers']) - 1 ? ', ' : '' ?><?php endforeach; ?></dd>
          <?php if ($d['special_requests'] !== ''): ?><dt>Special requests</dt><dd><?= nl2br(e($d['special_requests'])) ?></dd><?php endif; ?>
        </dl></div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:22px">
        <form method="post" action="" data-once>
          <?= csrf_field() ?><input type="hidden" name="package_id" value="<?= $pid ?>"><input type="hidden" name="action" value="confirm">
          <input type="hidden" name="token" value="<?= e($draft['token']) ?>">
          <button class="btn btn-lg" type="submit">Confirm booking &amp; continue to payment</button>
        </form>
        <form method="post" action="">
          <?= csrf_field() ?><input type="hidden" name="package_id" value="<?= $pid ?>"><input type="hidden" name="action" value="edit">
          <input type="hidden" name="travel_date" value="<?= e($d['travel_date']) ?>"><input type="hidden" name="guests" value="<?= (int) $d['guests'] ?>">
          <input type="hidden" name="contact_name" value="<?= e($d['contact_name']) ?>"><input type="hidden" name="contact_email" value="<?= e($d['contact_email']) ?>">
          <input type="hidden" name="contact_phone" value="<?= e($d['contact_phone']) ?>"><input type="hidden" name="special_requests" value="<?= e($d['special_requests']) ?>">
          <?php foreach ($d['travellers'] as $i => $t): ?><input type="hidden" name="traveller_name[<?= $i ?>]" value="<?= e($t['name']) ?>"><input type="hidden" name="traveller_age[<?= $i ?>]" value="<?= e($t['age']) ?>"><?php endforeach; ?>
          <button class="btn btn-outline btn-lg" type="submit">Edit details</button>
        </form>
      </div>
      <p class="muted small" style="margin-top:14px">Next you'll see a demonstration checkout. Your booking is created as <strong>Pending</strong> and confirmed by our team.</p>
    </div>
    <aside class="summary"><img src="<?= e(pkg_image_url($pkg['image'])) ?>" alt=""><div class="inner">
      <h3 style="margin-bottom:6px"><?= e($pkg['title']) ?></h3><p class="muted small"><?= e($pkg['location']) ?> · <?= e($pkg['duration']) ?></p>
      <div class="row"><span><?= e(money($pkg['price'])) ?> × <?= (int) $d['guests'] ?> guests</span><span><?= e(money($total)) ?></span></div>
      <div class="row"><span>Taxes &amp; fees</span><span>Included</span></div>
      <div class="row total"><span>Total</span><span><?= e(money($total)) ?></span></div></div></aside>

  <?php else: ?>
    <form id="booking-form" class="panel" method="post" action="" novalidate data-price="<?= e((float) $pkg['price']) ?>">
      <?= csrf_field() ?><input type="hidden" name="package_id" value="<?= $pid ?>"><input type="hidden" name="action" value="review">
      <h2>Trip details</h2>
      <?php render_errors($errors); ?>
      <div class="form-grid">
        <div class="form-row"><label class="lbl" for="travel_date">Travel date</label>
          <input class="<?= isset($errors['travel_date']) ? 'invalid' : '' ?>" id="travel_date" name="travel_date" type="date" required min="<?= e(earliest_travel_date()) ?>" max="<?= e(latest_travel_date()) ?>" value="<?= e($data['travel_date']) ?>">
          <div class="hint">Trip length: <?= e($pkg['duration']) ?>. Your return date is worked out for you.</div></div>
        <div class="form-row"><label class="lbl" for="guests">Number of guests</label>
          <input class="<?= isset($errors['guests']) ? 'invalid' : '' ?>" id="guests" name="guests" type="number" required min="1" max="<?= $maxGuests ?>" value="<?= e($data['guests']) ?>">
          <div class="hint">Up to <?= $maxGuests ?> guests for this tour.</div></div>
      </div>
      <h3 style="margin-top:12px">Lead traveller</h3>
      <div class="form-row"><label class="lbl" for="contact_name">Full name</label>
        <input class="<?= isset($errors['contact_name']) ? 'invalid' : '' ?>" id="contact_name" name="contact_name" type="text" required maxlength="100" value="<?= e($data['contact_name']) ?>" autocomplete="name"></div>
      <div class="form-grid">
        <div class="form-row"><label class="lbl" for="contact_email">Email</label>
          <input class="<?= isset($errors['contact_email']) ? 'invalid' : '' ?>" id="contact_email" name="contact_email" type="email" required maxlength="150" value="<?= e($data['contact_email']) ?>" autocomplete="email"></div>
        <div class="form-row"><label class="lbl" for="contact_phone">Phone</label>
          <input class="<?= isset($errors['contact_phone']) ? 'invalid' : '' ?>" id="contact_phone" name="contact_phone" type="tel" required maxlength="20" value="<?= e($data['contact_phone']) ?>" autocomplete="tel"></div>
      </div>
      <h3 style="margin-top:12px">Other travellers</h3>
      <div id="travellers">
        <?php $n = max(1, (int) $data['guests']); for ($i = 1; $i < min($n, $maxGuests); $i++): ?>
          <div class="traveller-row">
            <div><label class="lbl" for="tn<?= $i ?>">Traveller <?= $i + 1 ?> - full name</label>
              <input class="<?= isset($errors['traveller_' . $i]) ? 'invalid' : '' ?>" type="text" id="tn<?= $i ?>" name="traveller_name[<?= $i ?>]" maxlength="100" required value="<?= e($data['traveller_name'][$i] ?? '') ?>"></div>
            <div><label class="lbl" for="ta<?= $i ?>">Age</label><input type="number" id="ta<?= $i ?>" name="traveller_age[<?= $i ?>]" min="0" max="110" value="<?= e($data['traveller_age'][$i] ?? '') ?>"></div>
          </div>
        <?php endfor; ?>
      </div>
      <div class="form-row"><label class="lbl" for="special_requests">Special requests <span class="muted">(optional)</span></label>
        <textarea id="special_requests" name="special_requests" rows="3" maxlength="1000" placeholder="Dietary needs, accessibility, celebrations…"><?= e($data['special_requests']) ?></textarea></div>
      <button class="btn btn-lg" type="submit">Review booking</button>
    </form>
    <aside class="summary"><img src="<?= e(pkg_image_url($pkg['image'])) ?>" alt=""><div class="inner">
      <h3 style="margin-bottom:6px"><?= e($pkg['title']) ?></h3><p class="muted small"><?= e($pkg['location']) ?> · <?= e($pkg['duration']) ?></p>
      <div class="row"><span><?= e(money($pkg['price'])) ?> × <span data-guest-count>2</span> guests</span><span data-total>—</span></div>
      <div class="row"><span>Taxes &amp; fees</span><span>Included</span></div>
      <div class="row total"><span>Estimated total</span><span data-total>—</span></div>
      <p class="muted small" style="margin:12px 0 0">The final amount is confirmed by our server on the next step.</p></div></aside>
  <?php endif; ?>
  </div>
</div>
<?php page_footer();
