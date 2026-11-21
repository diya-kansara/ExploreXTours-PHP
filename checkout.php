<?php
/** Demonstration checkout. NOT a real payment gateway: no card details are requested, sent or stored. */
require __DIR__ . '/includes/bootstrap.php';
$user = require_user();
$uid = (int) $user['id'];

$ref = get_str('ref', 24) ?: post_str('ref', 24);
$b = user_booking_by_ref($ref, $uid);                   // ownership enforced
if (!$b) {
    abort(404, 'We could not find that booking in your account.');
}
if (!in_array($b['status'], ['Pending', 'Confirmed'], true)) {
    flash('info', 'This booking is ' . strtolower($b['status']) . ' and cannot be paid.');
    redirect('user/booking_view.php?ref=' . rawurlencode($ref));
}
if (booking_paid((int) $b['id'])) {
    redirect('booking_confirmation.php?ref=' . rawurlencode($ref));
}

if (is_post()) {
    csrf_verify();
    $method  = post_str('method', 30);
    $outcome = post_str('outcome', 10) === 'failure' ? 'failure' : 'success';
    $result = db_transaction(function () use ($b, $method, $outcome) {
        // lock the booking row so two simultaneous clicks cannot both record a successful payment
        db_one('SELECT id FROM bookings WHERE id = ? FOR UPDATE', 'i', [(int) $b['id']]);
        if (booking_paid((int) $b['id'])) {
            return ['status' => 'Success', 'ref' => null, 'duplicate' => true];
        }
        return record_simulated_payment($b, $method, $outcome);
    });
    if ($result['status'] === 'Success') {
        redirect('booking_confirmation.php?ref=' . rawurlencode($ref));
    }
    flash('error', 'Demo payment failed (transaction ' . $result['ref'] . '). Nothing was charged. You can try again.');
    redirect('checkout.php?ref=' . rawurlencode($ref));
}

$failedBefore = (int) db_value("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'Failed'", 'i', [(int) $b['id']]);
page_header('Checkout', ['active' => 'packages.php']);
?>
<div class="container" style="padding-top:36px">
  <h1 style="font-size:2.2rem">Checkout</h1>
  <?php booking_steps(3); ?>
  <div class="checkout">
    <form class="panel" method="post" action="" data-once>
      <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($ref) ?>">
      <div class="demo-banner" role="note"><span>⚠️</span><span><strong>Demonstration payment.</strong> This is a simulation for a portfolio project. No real money is charged and you must <strong>never</strong> enter real card details. This page doesn't ask for any.</span></div>
      <h2>Choose a demo payment method</h2>
      <div class="pay-options">
        <?php foreach (PAYMENT_METHODS as $i => $m): ?>
          <label class="pay-option"><input type="radio" name="method" value="<?= e($m) ?>" <?= $i === 0 ? 'checked' : '' ?>> <span><strong><?= e($m) ?></strong><br><span class="muted small">Simulated, no details required</span></span></label>
        <?php endforeach; ?>
      </div>
      <p class="muted">Choose how the simulated gateway should respond so you can see both outcomes.</p>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button class="btn btn-lg" type="submit" name="outcome" value="success">Pay <?= e(money($b['total_amount'])) ?> (simulate success)</button>
        <button class="btn btn-outline btn-lg" type="submit" name="outcome" value="failure">Simulate failed payment</button>
      </div>
      <?php if ($failedBefore): ?><p class="muted small" style="margin-top:14px"><?= $failedBefore ?> earlier demo attempt<?= $failedBefore > 1 ? 's' : '' ?> failed. No charge was made.</p><?php endif; ?>
      <p style="margin:22px 0 0"><a href="<?= e(url('user/booking_view.php?ref=' . rawurlencode($ref))) ?>">Pay later from my dashboard</a></p>
    </form>
    <aside class="summary"><img src="<?= e(pkg_image_url($b['package_image'])) ?>" alt=""><div class="inner">
      <h3 style="margin-bottom:4px"><?= e($b['package_title']) ?></h3>
      <p class="muted small">Ref <strong><?= e($b['booking_ref']) ?></strong></p>
      <div class="row"><span>Dates</span><span><?= e(fmt_date($b['travel_date'])) ?> → <?= e(fmt_date($b['return_date'], 'd M')) ?></span></div>
      <div class="row"><span><?= e(money($b['unit_price'])) ?> × <?= (int) $b['guests'] ?> guests</span><span><?= e(money($b['total_amount'])) ?></span></div>
      <div class="row total"><span>Total due</span><span><?= e(money($b['total_amount'])) ?></span></div></div></aside>
  </div>
</div>
<?php page_footer();
