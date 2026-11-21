<?php
require __DIR__ . '/includes/bootstrap.php';
$user = require_user();
$b = user_booking_by_ref(get_str('ref', 24), (int) $user['id']);     // ownership enforced
if (!$b) {
    abort(404, 'We could not find that booking in your account.');
}
$payment = db_one("SELECT transaction_ref, method, amount, created_at FROM payments WHERE booking_id = ? AND status = 'Success' ORDER BY id DESC LIMIT 1", 'i', [(int) $b['id']]);
page_header('Booking received', ['active' => 'packages.php']);
?>
<div class="container narrow" style="padding-bottom:80px">
  <div class="confirm-hero">
    <div class="tick">✓</div>
    <h1>Your booking is in!</h1>
    <p class="lead">We've received your request<?= $payment ? ' and your demo payment' : '' ?>. Our team will confirm it shortly.</p>
    <p class="ref" aria-label="Booking reference"><?= e($b['booking_ref']) ?></p>
  </div>
  <div class="panel"><dl class="kv">
    <dt>Tour</dt><dd><?= e($b['package_title']) ?> · <?= e($b['package_location']) ?></dd>
    <dt>Travel dates</dt><dd><?= e(fmt_date($b['travel_date'], 'D, d M Y')) ?> → <?= e(fmt_date($b['return_date'], 'D, d M Y')) ?></dd>
    <dt>Guests</dt><dd><?= (int) $b['guests'] ?></dd>
    <dt>Total</dt><dd><strong><?= e(money($b['total_amount'])) ?></strong></dd>
    <dt>Booking status</dt><dd><?= status_badge($b['status']) ?></dd>
    <dt>Payment</dt><dd><?php if ($payment): ?><span class="badge badge-ok">Paid (demo)</span> <code><?= e($payment['transaction_ref']) ?></code> · <?= e($payment['method']) ?><?php else: ?><span class="badge badge-muted">Unpaid</span><?php endif; ?></dd>
  </dl>
  <?php if ($payment): ?><p class="muted small" style="margin:14px 0 0">This was a simulated payment for demonstration. No real money was charged.</p><?php endif; ?></div>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:26px">
    <a class="btn" href="<?= e(url('user/booking_view.php?ref=' . rawurlencode($b['booking_ref']))) ?>">View booking</a>
    <?php if (!$payment): ?><a class="btn btn-outline" href="<?= e(url('checkout.php?ref=' . rawurlencode($b['booking_ref']))) ?>">Pay now (demo)</a><?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url('packages.php')) ?>">Keep exploring</a>
  </div>
</div>
<?php page_footer();
