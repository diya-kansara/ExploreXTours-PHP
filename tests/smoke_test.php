<?php
/**
 * ExploreX Tours - automated end-to-end + security test suite (HTTP level).
 *
 *   php tests/smoke_test.php http://localhost/ExploreXTours-PHP
 *
 * Needs: PHP CLI with curl + gd, the site running, migrations 001-004 and
 * database/sample_data.sql imported (it logs in as the sample customers), and an admin account:
 *   ADMIN_EMAIL=... ADMIN_PASSWORD=... php tests/smoke_test.php <base-url>
 * The suite creates its own throw-away customers / package and cleans up what it can.
 */
if (PHP_SAPI !== 'cli') { exit('CLI only'); }
$BASE = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$ADMIN_EMAIL = getenv('ADMIN_EMAIL') ?: '';
$ADMIN_PASSWORD = getenv('ADMIN_PASSWORD') ?: '';

// use the application's own DB layer for fixtures / cleanup / expected values (no mysql CLI needed)
require __DIR__ . '/../includes/bootstrap.php';
db_exec('DELETE FROM login_attempts');
db_exec("DELETE FROM reviews WHERE booking_id IN (SELECT id FROM bookings WHERE booking_ref = 'EXSAMPLE-006')");   // make the suite re-runnable
$pass = 0; $fail = 0; $log = [];
function t(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail, $log;
    $ok ? $pass++ : $fail++;
    $line = ($ok ? '  PASS  ' : '  FAIL  ') . $name . ($ok || $detail === '' ? '' : "  -> $detail");
    $log[] = $line;
    echo $line . "\n";
}
function section(string $s): void { global $log; echo "\n== $s\n"; $log[] = "\n== $s"; }

class Client
{
    public string $jar; public array $last = [];
    public function __construct(public string $base) { $this->jar = tempnam(sys_get_temp_dir(), 'exj'); }
    public function req(string $method, string $path, array $data = [], array $opts = []): array
    {
        $url = preg_match('#^https?://#', $path) ? $path : $this->base . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        $headers = $opts['headers'] ?? [];
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_FOLLOWLOCATION => $opts['follow'] ?? true, CURLOPT_MAXREDIRS => 6, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => $headers]);
        if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['multipart'] ?? http_build_query($data)); }
        $raw = curl_exec($ch);
        $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $r = ['code' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'headers' => substr((string) $raw, 0, $hs), 'body' => substr((string) $raw, $hs), 'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)];
        curl_close($ch);
        return $this->last = $r;
    }
    public function get(string $p, array $o = []): array { return $this->req('GET', $p, [], $o); }
    public function post(string $p, array $d = [], array $o = []): array { return $this->req('POST', $p, $d, $o); }
    public function csrf(string $page = 'index.php'): string
    {
        $r = $this->get($page);
        preg_match('/name="csrf-token" content="([a-f0-9]+)"/', $r['body'], $m);
        return $m[1] ?? '';
    }
    /** POST with a fresh CSRF token fetched from $tokenPage */
    public function postc(string $p, array $d = [], string $tokenPage = 'index.php', array $o = []): array
    {
        $d['_csrf'] = $this->csrf($tokenPage);
        return $this->post($p, $d, $o);
    }
}
function has(array $r, string $needle): bool { return str_contains($r['body'], $needle); }
function future(int $days): string { return date('Y-m-d', strtotime("+$days days")); }
function bookingRef(string $url): string { preg_match('/ref=([A-Z0-9\-]+)/', $url, $m); return $m[1] ?? ''; }

// ---------------------------------------------------------------------------
section('1. Public pages and discovery');
$pub = new Client($BASE);
foreach (['index.php' => 'Featured tour packages', 'packages.php' => 'Find your next journey', 'about.php' => 'About', 'contact.php' => 'We\'d love to hear', 'package_details.php?id=1' => 'Day-by-day itinerary'] as $p => $needle) {
    $r = $pub->get($p); t("GET $p returns 200 and content", $r['code'] === 200 && has($r, $needle), "code {$r['code']}");
}
$r = $pub->get('packages.php?q=goa'); t('Search "goa" finds Beach Paradise only', has($r, 'Beach Paradise') && !has($r, 'Mountain Trek'));
$r = $pub->get('packages.php?price=0-20000&sort=price_asc'); t('Price filter < 20,000 excludes Paris', has($r, 'Desert Safari') && !has($r, 'Parisian Romance'));
$r = $pub->get('packages.php?days=5-99'); t('Duration filter 5+ days returns Safari Adventure', has($r, 'Safari Adventure') && !has($r, 'Beach Paradise'));
$r = $pub->get('packages.php?category=romantic-escapes'); t('Category filter works', has($r, 'Parisian') && !has($r, 'Desert Safari'));
$r = $pub->get('packages.php?q=zzzzqqq'); t('No-result search shows empty state', has($r, 'No tours match'));
$r = $pub->get('packages.php?sort=price_desc'); $p1 = strpos($r['body'], 'Parisian'); $p2 = strpos($r['body'], 'Desert Safari'); t('Sort by price high to low orders cards', $p1 !== false && $p2 !== false && $p1 < $p2);
$r = $pub->get('package_details.php?id=99999'); t('Unknown package returns 404', $r['code'] === 404);
$r = $pub->get('package_details.php?id=abc'); t('Non-numeric package id returns 404', $r['code'] === 404);
$r = $pub->get('nonexistent-admin-register');
$r = $pub->get('admin/admin_register.php'); t('Public admin registration page is gone', $r['code'] === 404);
$r = $pub->get('hash.php'); t('hash.php helper removed', $r['code'] === 404);

// ---------------------------------------------------------------------------
section('2. Security: injection, XSS, headers');
foreach (["' OR '1'='1", "1; DROP TABLE users;--", "\" OR 1=1 --", "%' UNION SELECT 1,2,3 -- "] as $payload) {
    $r = $pub->get('packages.php?q=' . urlencode($payload));
    t('SQLi payload in search is harmless: ' . substr($payload, 0, 16), $r['code'] === 200 && !has($r, 'SQL syntax') && has($r, 'No tours match'), "code {$r['code']}");
}
$r = $pub->get('package_details.php?id=' . urlencode('1 OR 1=1')); t('SQLi in package id rejected', $r['code'] === 404);
$r = $pub->get('packages.php?sort=' . urlencode('p.id; DROP TABLE packages')); t('ORDER BY injection whitelisted', $r['code'] === 200 && has($r, 'Beach Paradise'));
$r = $pub->get('packages.php?q=' . urlencode('<script>alert(1)</script>')); t('Reflected XSS escaped in search', !has($r, '<script>alert(1)</script>') && has($r, '&lt;script&gt;'));
$r = $pub->get('index.php');
foreach (['X-Frame-Options', 'X-Content-Type-Options', 'Content-Security-Policy', 'Referrer-Policy'] as $h) { t("Security header $h present", stripos($r['headers'], $h . ':') !== false); }
$fresh = (new Client($BASE))->get('index.php');
t('Session cookie is HttpOnly + SameSite', (bool) preg_match('/Set-Cookie: EXPLOREXSESSID=[^;]+;.*HttpOnly/i', $fresh['headers']) && stripos($fresh['headers'], 'SameSite=Lax') !== false);
$r = $pub->get('includes/db.php'); t('includes/ code is not leaking output (blank or error)', !has($r, 'mysqli_stmt') || $r['code'] >= 400 || trim($r['body']) === '');
$r = $pub->get('admin/_init.php'); t('Admin include file does not expose data', !has($r, 'require_admin();') );

// ---------------------------------------------------------------------------
section('3. Customer: registration, login, session');
$u1 = new Client($BASE);
$email1 = 'test.' . bin2hex(random_bytes(4)) . '@example.com';
$xss = '<script>alert(7)</script>Test';
$r = $u1->postc('user/register.php', ['name' => $xss, 'email' => $email1, 'password' => 'short', 'confirm_password' => 'short', 'terms' => '1'], 'user/register.php');
t('Weak password rejected', has($r, 'at least 8 characters'));
$r = $u1->postc('user/register.php', ['name' => 'Test', 'email' => 'not-an-email', 'password' => 'Passw0rdx', 'confirm_password' => 'Passw0rdx', 'terms' => '1'], 'user/register.php');
t('Invalid email rejected', has($r, 'valid email'));
$r = $u1->postc('user/register.php', ['name' => 'Test', 'email' => $email1, 'password' => 'Passw0rdx', 'confirm_password' => 'Different1', 'terms' => '1'], 'user/register.php');
t('Password mismatch rejected', has($r, 'do not match'));
$sidBefore = '';
$u1->get('user/register.php'); $c = file_get_contents($u1->jar); preg_match('/EXPLOREXSESSID\s+(\S+)/', $c, $m); $sidBefore = $m[1] ?? '';
$r = $u1->postc('user/register.php', ['name' => $xss, 'email' => $email1, 'password' => 'Passw0rdx', 'confirm_password' => 'Passw0rdx', 'terms' => '1'], 'user/register.php');
t('Registration succeeds and lands on dashboard', has($r, 'Upcoming trips'), "url {$r['url']}");
$c = file_get_contents($u1->jar); preg_match('/EXPLOREXSESSID\s+(\S+)/', $c, $m); t('Session ID regenerated after registration/login (fixation)', ($m[1] ?? '') !== '' && $m[1] !== $sidBefore);
t('User name is HTML-escaped on dashboard (stored XSS)', !has($r, '<script>alert(7)</script>') && has($r, '&lt;script&gt;'));
$r = $pub->postc('user/register.php', ['name' => 'Dup', 'email' => $email1, 'password' => 'Passw0rdx', 'confirm_password' => 'Passw0rdx', 'terms' => '1'], 'user/register.php');
t('Duplicate email rejected', has($r, 'already exists'));
$u1->postc('user/logout.php', [], 'user/dashboard.php');
$r = $u1->get('user/dashboard.php'); t('Logout (POST) ends session', has($r, 'Log in') && !has($r, 'Upcoming trips'));
$r = $u1->post('user/login.php', ['email' => $email1, 'password' => 'Passw0rdx']); t('Login without CSRF token rejected (419)', $r['code'] === 419);
$r = $u1->postc('user/login.php', ['email' => $email1, 'password' => 'WrongPass1'], 'user/login.php'); t('Wrong password shows generic error', has($r, 'Incorrect email or password'));
$r = $u1->postc('user/login.php', ['email' => 'nobody@example.com', 'password' => 'WrongPass1'], 'user/login.php'); t('Unknown email gives the same generic error', has($r, 'Incorrect email or password'));
$r = $u1->postc('user/login.php', ['email' => $email1, 'password' => 'Passw0rdx', 'next' => '//evil.example.com'], 'user/login.php');
t('Open redirect via next= ignored', !str_contains($r['url'], 'evil.example.com') && has($r, 'Upcoming trips'));
$u1->postc('user/logout.php', [], 'user/dashboard.php');
$r = $u1->postc('user/login.php', ['email' => $email1, 'password' => 'Passw0rdx', 'next' => 'packages.php?q=goa'], 'user/login.php');
t('Valid login honours safe next= path', str_contains($r['url'], 'packages.php'));
$r = $pub->get('user/dashboard.php'); t('Dashboard requires login', has($r, 'Log in') && !has($r, 'Booking history'));
$r = $pub->get('book.php?package_id=1'); t('Booking page requires login', str_contains($r['url'], 'login.php'));

// brute force throttle (uses a random e-mail so real users are not locked)
$bf = new Client($BASE); $victim = 'victim.' . bin2hex(random_bytes(3)) . '@example.com'; $locked = false;
for ($i = 0; $i < 7; $i++) { $r = $bf->postc('user/login.php', ['email' => $victim, 'password' => 'Nope12345'], 'user/login.php'); if (has($r, 'Too many failed attempts')) { $locked = true; break; } }
t('Brute-force lockout after repeated failures', $locked);

// ---------------------------------------------------------------------------
section('4. Customer: wishlist');
$wh = ['X-Requested-With: fetch', 'Accept: application/json'];
$r = $pub->postc('wishlist_toggle.php', ['package_id' => 1], 'index.php', ['headers' => $wh, 'follow' => false]); t('Guest wishlist toggle returns 401', $r['code'] === 401);
$tok = $u1->csrf('packages.php');
$r = $u1->post('wishlist_toggle.php', ['package_id' => 1, '_csrf' => $tok], ['headers' => $wh]); $j = json_decode($r['body'], true);
t('Add to wishlist', ($j['saved'] ?? null) === true && ($j['count'] ?? 0) === 1, $r['body']);
$r = $u1->post('wishlist_toggle.php', ['package_id' => 3, '_csrf' => $tok], ['headers' => $wh]); $j = json_decode($r['body'], true); t('Add a second package', ($j['count'] ?? 0) === 2);
$r = $u1->get('user/wishlist.php'); t('Wishlist page shows saved tours with detail links', has($r, 'Beach Paradise') && has($r, 'Desert Safari') && has($r, 'package_details.php?id=1'));
$r = $u1->post('wishlist_toggle.php', ['package_id' => 1, '_csrf' => $tok], ['headers' => $wh]); $j = json_decode($r['body'], true); t('Toggle removes (no duplicate entries)', ($j['saved'] ?? null) === false && ($j['count'] ?? 0) === 1);
$r = $u1->post('wishlist_toggle.php', ['package_id' => 1], ['headers' => $wh]); t('Wishlist POST without CSRF rejected', $r['code'] === 419);
$r = $u1->post('wishlist_toggle.php', ['package_id' => 99999, '_csrf' => $tok], ['headers' => $wh]); t('Wishlist for missing package -> 404', $r['code'] === 404);
$r = $u1->get('wishlist_toggle.php?package_id=1'); t('Wishlist GET not allowed (405)', $r['code'] === 405);

// ---------------------------------------------------------------------------
section('5. Booking workflow, payment simulation, ownership');
$book = function (Client $c, array $over = []) {
    return $c->postc('book.php?package_id=1', array_merge([
        'package_id' => 1, 'action' => 'review', 'travel_date' => future(30), 'guests' => 2, 'contact_name' => 'Test Traveller',
        'contact_email' => 'trav@example.com', 'contact_phone' => '+91 98765 43210', 'special_requests' => 'Window seat',
        'traveller_name' => [1 => 'Second Traveller'], 'traveller_age' => [0 => 30, 1 => 28],
    ], $over), 'book.php?package_id=1');
};
$r = $book($u1, ['travel_date' => date('Y-m-d', strtotime('-3 days'))]); t('Past travel date rejected', has($r, 'on or after'));
$r = $book($u1, ['travel_date' => 'garbage']); t('Invalid date rejected', has($r, 'valid travel date'));
$r = $book($u1, ['guests' => 0]); t('Zero guests rejected', has($r, 'Guests must be between'));
$r = $book($u1, ['guests' => 99, 'traveller_name' => []]); t('Too many guests rejected', has($r, 'Guests must be between'));
$r = $book($u1, ['contact_email' => 'bad']); t('Invalid contact email rejected', has($r, 'valid email'));
$r = $book($u1, ['contact_phone' => 'abc']); t('Invalid phone rejected', has($r, 'valid phone'));
$r = $book($u1, ['traveller_name' => [1 => '']]); t('Missing second traveller name rejected', has($r, 'name of traveller 2'));
$r = $book($u1, ['guests' => '2; DROP TABLE bookings']); t('Non-numeric guests rejected', has($r, 'Guests must be between'));
$r = $book($u1, ['total_amount' => '1', 'unit_price' => '1', 'price' => '1']);
t('Review step shows server-calculated total (₹30,000), ignoring tampered price', has($r, 'Review your booking') && has($r, '₹30,000') && !has($r, '₹1<'), substr(strip_tags($r['body']), 0, 0));
preg_match('/name="token" value="([a-f0-9]{32})"/', $r['body'], $m); $token = $m[1] ?? '';
t('Review step issues one-time submit token', strlen($token) === 32);
$confirm = fn() => $u1->postc('book.php?package_id=1', ['package_id' => 1, 'action' => 'confirm', 'token' => $token], 'book.php?package_id=1', ['follow' => false]);
$r1 = $confirm(); $loc = ''; if (preg_match('/Location: (\S+)/i', $r1['headers'], $m)) { $loc = trim($m[1]); }
$ref1 = bookingRef($loc); t('Confirm creates booking and redirects to checkout with reference', $r1['code'] === 302 && $ref1 !== '' && str_starts_with($ref1, 'EX'), "loc=$loc");
$r2 = $confirm(); $loc2 = ''; if (preg_match('/Location: (\S+)/i', $r2['headers'], $m)) { $loc2 = trim($m[1]); }
t('Duplicate submission (same token) does NOT create a second booking', bookingRef($loc2) === $ref1, "loc2=$loc2");
$r = $u1->get('user/bookings.php'); t('Booking history lists exactly one new booking', substr_count($r['body'], '<strong>' . $ref1 . '</strong>') === 1 && has($r, '₹30,000'));
$r = $u1->get('user/booking_view.php?ref=' . $ref1); t('Booking details show status Pending, travellers and Unpaid', has($r, 'Pending') && has($r, 'Second Traveller') && has($r, 'Unpaid'));
$r = $u1->get('checkout.php?ref=' . $ref1); t('Checkout shows demo banner and no card fields', has($r, 'Demonstration payment') && !preg_match('/card.?number|cvv|cvc|expiry/i', preg_replace('/Demo Card/', '', $r['body'])));
$r = $u1->postc('checkout.php?ref=' . $ref1, ['ref' => $ref1, 'method' => 'Demo UPI', 'outcome' => 'failure'], 'checkout.php?ref=' . $ref1); t('Simulated payment FAILURE recorded with transaction ref', has($r, 'Demo payment failed') && has($r, 'TXN-DEMO-'));
$r = $u1->get('user/payments.php'); t('Payment history shows Failed attempt', has($r, 'Failed') && has($r, 'simulated'));
$r = $u1->postc('checkout.php?ref=' . $ref1, ['ref' => $ref1, 'method' => 'Demo Card', 'outcome' => 'success'], 'checkout.php?ref=' . $ref1); t('Simulated payment SUCCESS leads to confirmation page', has($r, 'Your booking is in') && has($r, $ref1) && has($r, 'Paid (demo)'));
$r = $u1->get('checkout.php?ref=' . $ref1); t('Already-paid booking cannot be paid twice', has($r, 'Your booking is in'));
$r = $u1->get('user/dashboard.php'); t('Dashboard shows the upcoming trip', has($r, $ref1) && has($r, 'Beach Paradise'));

$u2 = new Client($BASE); $email2 = 'other.' . bin2hex(random_bytes(4)) . '@example.com';
$u2->postc('user/register.php', ['name' => 'Other User', 'email' => $email2, 'password' => 'Passw0rdx', 'confirm_password' => 'Passw0rdx', 'terms' => '1'], 'user/register.php');
$r = $u2->get('user/booking_view.php?ref=' . $ref1); t('OWNERSHIP: other customer cannot view booking', $r['code'] === 404 && !has($r, 'Second Traveller'));
$r = $u2->get('checkout.php?ref=' . $ref1); t('OWNERSHIP: other customer cannot open checkout', $r['code'] === 404);
$r = $u2->get('booking_confirmation.php?ref=' . $ref1); t('OWNERSHIP: other customer cannot open confirmation', $r['code'] === 404);
$r = $u2->postc('user/cancel_booking.php', ['ref' => $ref1, 'reason' => 'hack'], 'user/dashboard.php'); t('OWNERSHIP: other customer cannot cancel booking', $r['code'] === 404);
$r = $u2->get('user/bookings.php'); t('OWNERSHIP: other customer history does not list it', !has($r, $ref1));
$r = $u2->postc('checkout.php?ref=' . $ref1, ['ref' => $ref1, 'method' => 'Demo Card', 'outcome' => 'success'], 'user/dashboard.php'); t('OWNERSHIP: other customer cannot pay for it', $r['code'] === 404);

$r = $u1->post('user/cancel_booking.php', ['ref' => $ref1]); t('Cancellation request without CSRF rejected', $r['code'] === 419);
$r = $u1->postc('user/cancel_booking.php', ['ref' => $ref1, 'reason' => 'Plans changed'], 'user/dashboard.php'); t('Customer can request cancellation', has($r, 'Cancellation request sent') && has($r, 'Cancellation requested'));
$r = $u1->postc('user/cancel_booking.php', ['ref' => $ref1, 'reason' => 'again'], 'user/dashboard.php'); t('Second cancellation request is refused', has($r, 'can no longer be cancelled'));
$r = $u1->get('user/review.php?ref=' . $ref1); t('Cannot review a non-completed booking', has($r, 'after your trip is marked as completed'));

// ---------------------------------------------------------------------------
section('6. Reviews (eligibility + duplicates)');
$sn = new Client($BASE);
$sn->postc('user/login.php', ['email' => 'sneha.iyer@example.com', 'password' => 'Demo@1234'], 'user/login.php');
$r = $sn->get('user/bookings.php'); preg_match_all('/EXSAMPLE-\d{3}/', $r['body'], $m); $sneRefs = array_unique($m[0]);
$completedRef = 'EXSAMPLE-006';
$r = $sn->get('user/review.php?ref=' . $completedRef); t('Eligible completed booking shows review form', has($r, 'Submit review'));
$r = $sn->postc('user/review.php?ref=' . $completedRef, ['ref' => $completedRef, 'rating' => 0, 'comment' => 'short'], 'user/review.php?ref=' . $completedRef); t('Review validation (rating + length)', has($r, 'star rating') && has($r, 'at least a sentence'));
$r = $sn->postc('user/review.php?ref=' . $completedRef, ['ref' => $completedRef, 'rating' => 4, 'title' => 'Lovely', 'comment' => 'A really lovely trip with great hotel choices.'], 'user/review.php?ref=' . $completedRef); t('Review submitted and awaits moderation', has($r, 'Thank you') && has($r, 'Pending'));
$r = $sn->get('user/review.php?ref=' . $completedRef); t('Duplicate review for the same booking blocked', has($r, 'already reviewed'));
$r = $u1->get('user/review.php?ref=' . $completedRef); t('OWNERSHIP: other customer cannot review someone else\'s booking', $r['code'] === 404);
$r = $pub->get('package_details.php?id=7'); t('Pending review is NOT public yet', !has($r, 'A really lovely trip'));

// ---------------------------------------------------------------------------
section('7. Customer: profile management');
$r = $u1->postc('user/profile.php', ['action' => 'profile', 'name' => 'Renamed User', 'email' => $email1, 'phone' => '+91 90000 00000'], 'user/profile.php'); t('Profile update saved', has($r, 'Profile updated') && has($r, 'Renamed User'), preg_replace('/\s+/', ' ', substr(strip_tags($r['body']), strpos(strip_tags($r['body']), 'Skip'), 500)));
$r = $u1->postc('user/profile.php', ['action' => 'profile', 'name' => 'X', 'email' => $email2, 'phone' => ''], 'user/profile.php'); t('Profile validation (name too short)', has($r, 'Please enter your name'));
$r = $u1->postc('user/profile.php', ['action' => 'profile', 'name' => 'Renamed User', 'email' => $email2, 'phone' => ''], 'user/profile.php'); t('Cannot take another account\'s email', has($r, 'already used'));
$r = $u1->postc('user/profile.php', ['action' => 'password', 'current_password' => 'wrong', 'new_password' => 'NewPassw0rd', 'confirm_password' => 'NewPassw0rd'], 'user/profile.php'); t('Password change needs correct current password', has($r, 'current password is incorrect'));
$r = $u1->postc('user/profile.php', ['action' => 'password', 'current_password' => 'Passw0rdx', 'new_password' => 'NewPassw0rd', 'confirm_password' => 'NewPassw0rd'], 'user/profile.php'); t('Password changed', has($r, 'Password changed'));
$u1->postc('user/logout.php', [], 'user/dashboard.php');
$r = $u1->postc('user/login.php', ['email' => $email1, 'password' => 'NewPassw0rd'], 'user/login.php'); t('Login works with the new password', has($r, 'Upcoming trips'));

// ---------------------------------------------------------------------------
section('8. Contact form and newsletter');
$r = $pub->postc('contact.php', ['name' => 'Tester', 'email' => 'bad', 'subject' => 'Hi', 'message' => 'x'], 'contact.php'); t('Contact validation', has($r, 'valid email'));
$r = $pub->post('contact.php', ['name' => 'Tester', 'email' => 'a@b.co', 'subject' => 'Subject here', 'message' => 'A proper message body.']); t('Contact POST without CSRF rejected', $r['code'] === 419);
$msgSubject = 'Automated test ' . bin2hex(random_bytes(3));
$r = $pub->postc('contact.php', ['name' => '<b>Tester</b>', 'email' => 'tester@example.com', 'subject' => $msgSubject, 'message' => 'A proper message body for testing <img src=x onerror=alert(1)>.'], 'contact.php'); t('Contact message sent', has($r, 'Your message has been sent'));
$r = $pub->postc('newsletter.php', ['email' => 'sub' . bin2hex(random_bytes(3)) . '@example.com', 'back' => 'index.php'], 'index.php'); t('Newsletter subscription works', has($r, 'on the list'));

// ---------------------------------------------------------------------------
section('9. Admin: authentication and authorisation');
$ad = new Client($BASE);
foreach (['admin/dashboard.php', 'admin/manage_packages.php', 'admin/manage_bookings.php', 'admin/manage_users.php', 'admin/reviews.php', 'admin/view_messages.php', 'admin/payments.php', 'admin/admins.php', 'admin/add_package.php'] as $p) {
    $r = $pub->get($p); t("Anonymous blocked from $p", str_contains($r['url'], 'admin/login.php') && has($r, 'Admin sign in'));
}
foreach (['admin/booking_action.php', 'admin/package_action.php', 'admin/user_action.php', 'admin/review_action.php', 'admin/message_action.php'] as $p) {
    $r = $pub->postc($p, ['id' => 1, 'action' => 'delete'], 'index.php', ['follow' => false]); t("Anonymous POST to $p does not act", in_array($r['code'], [302, 303], true) && str_contains($r['headers'], 'admin/login.php'));
}
foreach (['admin/dashboard.php', 'admin/manage_packages.php', 'admin/manage_users.php'] as $p) {
    $r = $u1->get($p); t("Customer session blocked from $p", str_contains($r['url'], 'admin/login.php'));
}
t('Admin credentials supplied for tests', $ADMIN_EMAIL !== '' && $ADMIN_PASSWORD !== '');
$r = $ad->postc('admin/login.php', ['email' => $ADMIN_EMAIL, 'password' => 'wrong-password'], 'admin/login.php'); t('Admin wrong password rejected', has($r, 'Incorrect email or password'));
$r = $ad->post('admin/login.php', ['email' => $ADMIN_EMAIL, 'password' => $ADMIN_PASSWORD]); t('Admin login without CSRF rejected', $r['code'] === 419);
$r = $ad->postc('admin/login.php', ['email' => $ADMIN_EMAIL, 'password' => $ADMIN_PASSWORD], 'admin/login.php'); t('Admin login succeeds', has($r, 'Recent bookings') && has($r, 'Revenue'));
t('Dashboard shows real statistics blocks and Chart.js canvases', has($r, 'chart-bookings') && has($r, 'chart-revenue') && has($r, 'chart-status') && has($r, 'Pending') && has($r, 'Needs attention'));
$expected = (float) db_value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('Confirmed','Completed')");
t('Dashboard revenue equals the SQL sum of eligible bookings', $expected > 0 && has($r, '₹' . number_format($expected)), "expected ₹" . number_format($expected));
$r = $ad->post('admin/booking_action.php', ['id' => 1, 'action' => 'status', 'to' => 'Cancelled']); t('Admin state change without CSRF rejected', $r['code'] === 419);
$r = $ad->get('admin/booking_action.php?id=1&action=status&to=Cancelled'); t('Admin state change via GET refused (405)', $r['code'] === 405);
$r = $ad->get('admin/package_action.php?id=1&action=delete'); t('Package delete via GET refused (405)', $r['code'] === 405);

// ---------------------------------------------------------------------------
section('10. Admin: package management and secure uploads');
function png(int $w = 640, int $h = 440): string { $im = imagecreatetruecolor($w, $h); imagefill($im, 0, 0, imagecolorallocate($im, 60, 120, 90)); ob_start(); imagepng($im); return ob_get_clean(); }
$tmp = sys_get_temp_dir();
file_put_contents("$tmp/ok.png", png());
file_put_contents("$tmp/evil.php.png", "<?php echo 'PWNED'; ?>" . str_repeat('A', 500));
file_put_contents("$tmp/evil.php", "<?php echo 'PWNED'; ?>");
file_put_contents("$tmp/huge.png", png(600, 400) . str_repeat("\0", 4 * 1024 * 1024));
file_put_contents("$tmp/fake.png", "GIF89a<?php system('id'); ?>");
$fields = fn(array $o = []) => array_merge(['_csrf' => $ad->csrf('admin/add_package.php'), 'title' => 'Test Tour ' . bin2hex(random_bytes(2)), 'location' => 'Testville', 'category_id' => 1, 'price' => '9999', 'duration_days' => 3, 'max_guests' => 6,
    'description' => 'A short test description for the package.', 'overview' => 'Long overview text.', 'is_active' => 1,
    'day_title' => ['Arrive and settle in', 'Explore the old town', 'Depart'], 'day_desc' => ['Check in', 'Walking tour', ''], 'hotel_name' => ['Hotel Test'], 'hotel_city' => ['Testville'], 'hotel_star' => ['4'], 'hotel_desc' => ['Cosy'],
    'inclusions' => "Breakfast\nTransfers", 'exclusions' => "Flights\nPersonal expenses"], $o);
$mp = function (array $f, ?string $file, string $mime = 'image/png', string $name = 'photo.png') {
    $flat = [];
    foreach ($f as $k => $v) { if (is_array($v)) { foreach ($v as $i => $x) { $flat["{$k}[{$i}]"] = $x; } } else { $flat[$k] = $v; } }
    if ($file) { $flat['image'] = new CURLFile($file, $mime, $name); }
    return $flat;
};
$r = $ad->post('admin/add_package.php', [], ['multipart' => $mp($fields(), "$tmp/evil.php.png", 'image/png', 'evil.png')]); t('Upload: PHP code disguised as PNG rejected', has($r, 'Only JPG, PNG or WebP') || has($r, 'not valid'));
$r = $ad->post('admin/add_package.php', [], ['multipart' => $mp($fields(), "$tmp/evil.php", 'application/x-php', 'evil.php')]); t('Upload: .php file rejected', has($r, 'Only JPG, PNG or WebP'));
$r = $ad->post('admin/add_package.php', [], ['multipart' => $mp($fields(), "$tmp/fake.png", 'image/png', 'fake.png')]); t('Upload: polyglot with image header rejected', has($r, 'not valid') || has($r, 'could not be processed') || has($r, 'Only JPG'));
$r = $ad->post('admin/add_package.php', [], ['multipart' => $mp($fields(), "$tmp/huge.png", 'image/png', 'huge.png')]); t('Upload: oversize file rejected', has($r, 'smaller than') || has($r, 'larger than the allowed size'));
$r = $ad->post('admin/add_package.php', [], ['multipart' => $mp($fields(), null)]); t('Upload: main image required on create', has($r, 'upload a main image'));
$r = $ad->post('admin/add_package.php', [], ['multipart' => $mp($fields(['price' => '-5', 'title' => '']), "$tmp/ok.png")]); t('Package validation (title + price)', has($r, 'Title is required') && has($r, 'valid price'));
$ff = $fields(); $title = $ff['title'];
$r = $ad->post('admin/add_package.php', [], ['multipart' => $mp($ff, "$tmp/ok.png")]); t('Package created with itinerary, hotel, inclusions', has($r, 'Package created') && has($r, $title), preg_replace('/\s+/', ' ', substr(strip_tags($r['body']), 0, 700)) . ' | title=' . $title);
preg_match('/edit_package\.php\?id=(\d+)/', $r['url'], $m); $newId = (int) ($m[1] ?? 0);
$files = glob(dirname(__DIR__) . '/uploads/packages/pkg_*'); t('Uploaded file stored with generated name in uploads/packages', count($files) >= 1 && !preg_match('/ok\.png|photo\.png/', implode(' ', $files)));
$r = $pub->get("package_details.php?id=$newId"); t('New package visible publicly with itinerary/hotel/inclusions', has($r, $title) && has($r, 'Explore the old town') && has($r, 'Hotel Test') && has($r, 'Breakfast') && has($r, 'Personal expenses') && has($r, '₹9,999'));
$r = $pub->get('packages.php?q=Testville'); t('New package searchable', has($r, $title));
$ff2 = $fields(['title' => $title . ' EDITED', 'price' => '12000', 'day_title' => ['Only day']]);
$r = $ad->post("admin/edit_package.php?id=$newId", [], ['multipart' => $mp($ff2, null)]); t('Package edit saved (image kept)', has($r, 'Package updated') && has($r, 'EDITED'));
$r = $pub->get("package_details.php?id=$newId"); t('Edit reflected on public page (price + itinerary replaced)', has($r, '₹12,000') && !has($r, 'Explore the old town') && has($r, 'Only day'));
$r = $ad->postc('admin/package_action.php', ['id' => $newId, 'action' => 'toggle'], 'admin/manage_packages.php'); t('Package can be hidden', has($r, 'visibility updated'));
$r = $pub->get("package_details.php?id=$newId"); t('Hidden package is 404 for customers', $r['code'] === 404);
$r = $ad->postc('admin/package_action.php', ['id' => 1, 'action' => 'delete'], 'admin/manage_packages.php'); t('Package with bookings cannot be deleted (history preserved)', has($r, 'cannot be deleted'));
$r = $ad->get('admin/manage_packages.php?q=Testville'); t('Admin package search works', has($r, 'Testville'));
$r = $ad->postc('admin/package_action.php', ['id' => $newId, 'action' => 'delete'], 'admin/manage_packages.php'); t('Package without bookings deleted', has($r, 'was deleted'));
$left = glob(dirname(__DIR__) . '/uploads/packages/pkg_*'); t('Image file removed from disk on delete', count($left) === 0, count($left) . ' left');

// ---------------------------------------------------------------------------
section('11. Admin: bookings, payments, users, reviews, messages');
$r = $ad->get('admin/manage_bookings.php'); t('Bookings list renders with references', has($r, $ref1) && has($r, 'EXSAMPLE-001'));
$r = $ad->get('admin/manage_bookings.php?q=' . $ref1); t('Booking search by reference', has($r, $ref1) && !has($r, 'EXSAMPLE-001'));
$r = $ad->get('admin/manage_bookings.php?status=Cancelled'); t('Booking filter by status', has($r, 'EXSAMPLE-012') && !has($r, $ref1));
$r = $ad->get('admin/manage_bookings.php?cancel=1'); t('Cancellation-request filter lists the customer request', has($r, $ref1));
$r = $ad->get('admin/manage_bookings.php?q=' . urlencode("' OR 1=1 --")); t('Admin search is injection-safe', $r['code'] === 200 && has($r, 'No bookings match'));
$bid = (int) db_value('SELECT id FROM bookings WHERE booking_ref = ?', 's', [$ref1]);
$r = $ad->get("admin/booking_view.php?id=$bid"); t('Admin booking detail shows cancellation request, travellers, payments', has($r, 'Cancellation requested') && has($r, 'Plans changed') && has($r, 'Second Traveller') && has($r, 'TXN-DEMO-'));
$r = $ad->postc('admin/booking_action.php', ['id' => $bid, 'action' => 'status', 'to' => 'Completed'], 'admin/manage_bookings.php'); t('Pending booking cannot jump straight to Completed', has($r, 'cannot be changed'));
$r = $ad->postc('admin/booking_action.php', ['id' => $bid, 'action' => 'status', 'to' => 'Confirmed'], 'admin/manage_bookings.php'); t('Admin confirms booking', has($r, 'is now Confirmed'));
$r = $ad->postc('admin/booking_action.php', ['id' => $bid, 'action' => 'status', 'to' => 'Completed'], 'admin/manage_bookings.php'); t('Cannot complete a trip that has not ended', has($r, 'only be marked completed after the trip'));
$r = $u1->get('user/booking_view.php?ref=' . $ref1); t('Customer sees Confirmed status', has($r, 'Confirmed'));
$r = $ad->postc('admin/booking_action.php', ['id' => $bid, 'action' => 'reject_cancel'], 'admin/manage_bookings.php'); t('Admin can decline cancellation request', has($r, 'request declined'));
$r = $ad->postc('admin/booking_action.php', ['id' => $bid, 'action' => 'status', 'to' => 'Pending'], 'admin/manage_bookings.php'); t('Invalid transition (Confirmed -> Pending) refused', has($r, 'cannot be changed'));
$r = $ad->postc('admin/booking_action.php', ['id' => $bid, 'action' => 'status', 'to' => 'Cancelled'], 'admin/manage_bookings.php'); t('Admin cancels booking', has($r, 'is now Cancelled'));
$r = $ad->postc('admin/booking_action.php', ['id' => $bid, 'action' => 'status', 'to' => 'Confirmed'], 'admin/manage_bookings.php'); t('Cancelled booking is final', has($r, 'cannot be changed'));
$r = $ad->get('admin/payments.php'); t('Payments page lists simulated records with demo notice', has($r, 'simulated demonstration payments') && has($r, 'TXN-DEMO-'));
$r = $ad->get('admin/payments.php?status=Failed'); t('Payment filter by status', has($r, 'Failed') && !has($r, 'TXN-DEMO-SAMPLE'));
$r = $ad->get('admin/manage_users.php?q=sneha'); t('Customer search', has($r, 'Sneha Iyer') && !has($r, 'Aarav'));
$uid = (int) db_value("SELECT id FROM users WHERE email = 'rohan.das@example.com'");
$r = $ad->get("admin/user_view.php?id=$uid"); t('Customer detail shows bookings and delete safeguard', has($r, 'Rohan Das') && has($r, "can't be deleted"));
$r = $ad->postc('admin/user_action.php', ['id' => $uid, 'action' => 'delete'], 'admin/manage_users.php'); t('Deleting customer with active bookings refused', has($r, 'cannot be deleted'));
$r = $ad->get('admin/reviews.php?status=Pending'); t('Review moderation lists pending reviews', has($r, 'A really lovely trip'));
preg_match('/name="id" value="(\d+)"><input type="hidden" name="action" value="Approved"/', $r['body'], $m); $rid = (int) ($m[1] ?? 0);
$r = $ad->postc('admin/review_action.php', ['id' => $rid, 'action' => 'Approved'], 'admin/reviews.php'); t('Admin approves review', has($r, 'Review approved'));
$r = $pub->get('package_details.php?id=8'); $r2 = $pub->get('package_details.php?id=7'); t('Approved review now public on package page', has($r, 'A really lovely trip') || has($r2, 'A really lovely trip'));
$r = $ad->get('admin/view_messages.php'); t('Contact messages listed (HTML escaped)', has($r, $msgSubject) && !has($r, '<img src=x onerror') && has($r, '&lt;img'));
preg_match('/name="id" value="(\d+)"><input type="hidden" name="action" value="read"/', $r['body'], $m); $mid = (int) ($m[1] ?? 0);
$r = $ad->postc('admin/message_action.php', ['id' => $mid, 'action' => 'read'], 'admin/view_messages.php'); t('Message can be marked read', $r['code'] === 200);
$r = $ad->get('admin/admins.php'); t('Super/administrator page reachable by admin', $r['code'] === 200 || $r['code'] === 403);
$r = $ad->postc('admin/profile.php', ['action' => 'password', 'current_password' => 'nope', 'new_password' => 'Abcdef12', 'confirm_password' => 'Abcdef12'], 'admin/profile.php'); t('Admin password change needs current password', has($r, 'current password is incorrect'));
$ad->postc('admin/logout.php', [], 'admin/dashboard.php'); $r = $ad->get('admin/dashboard.php'); t('Admin logout works', has($r, 'Admin sign in'));

// ---------------------------------------------------------------------------
section('12. Responsive markup sanity');
$r = $pub->get('index.php'); t('Viewport meta + skip link present', has($r, 'name="viewport"') && has($r, 'skip-link'));
$css = $pub->get('assets/css/app.css')['body']; t('CSS has tablet and mobile breakpoints', str_contains($css, '@media (max-width: 860px)') && str_contains($css, '@media (max-width: 620px)'));
$r = $pub->get('index.php'); preg_match_all('/(?:src|href)="(\/?[^"#]*\.(?:css|js|jpg|png|svg))"/', $r['body'], $m); $broken = [];
foreach (array_unique($m[1]) as $asset) { if (str_starts_with($asset, 'http')) continue; $x = $pub->get(preg_match('#^/#', $asset) ? $BASE . '/' . ltrim(preg_replace('#^' . preg_quote(parse_url($BASE, PHP_URL_PATH) ?? '', '#') . '#', '', $asset), '/') : $asset); if ($x['code'] !== 200) $broken[] = $asset; }
t('Home page assets all load (no broken CSS/JS/images)', !$broken, implode(', ', $broken));

// ---- cleanup of throw-away data created by this run ----
db_exec("DELETE FROM bookings WHERE user_id IN (SELECT id FROM users WHERE email LIKE 'test.%@example.com' OR email LIKE 'other.%@example.com')");
db_exec("DELETE FROM users WHERE email LIKE 'test.%@example.com' OR email LIKE 'other.%@example.com'");
db_exec("DELETE FROM contacts WHERE subject LIKE 'Automated test %'");
db_exec("DELETE FROM newsletter_subscribers WHERE email LIKE 'sub%@example.com'");
db_exec('DELETE FROM login_attempts');

echo "\n----------------------------------------\n";
echo "TOTAL: " . ($pass + $fail) . "   PASSED: $pass   FAILED: $fail\n";
@mkdir(__DIR__ . '/output', 0777, true);
file_put_contents(__DIR__ . '/output/last_run.txt', implode("\n", $log) . "\n\nTOTAL: " . ($pass + $fail) . "  PASSED: $pass  FAILED: $fail\n");
exit($fail ? 1 : 0);
