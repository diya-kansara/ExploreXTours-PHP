<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_user();
$uid = (int) $user['id'];

$b = user_booking_by_ref(get_str('ref', 24), $uid);     // ownership enforced in SQL (user_id = session user)
if (!$b) {
    abort(404, 'We could not find that booking in your account.');
}
$travellers = db_all('SELECT full_name, age, is_lead FROM booking_travellers WHERE booking_id = ? ORDER BY is_lead DESC, id', 'i', [(int) $b['id']]);
$payments = db_all('SELECT transaction_ref, amount, method, status, failure_reason, created_at FROM payments WHERE booking_id = ? ORDER BY id DESC', 'i', [(int) $b['id']]);
$paid = booking_paid((int) $b['id']);
$review = db_one('SELECT rating, status FROM reviews WHERE booking_id = ?', 'i', [(int) $b['id']]);

dash_open('bookings', 'Booking ' . $b['booking_ref'], $user);
?>
<p class="crumbs" style="margin-top:0"><a href="<?= e(url('user/bookings.php')) ?>">← All bookings</a></p>
<div style="display:flex;justify-content:space-between;gap:16px;align-items:start;flex-wrap:wrap">
  <div><h1 style="margin-bottom:.2rem"><?= e($b['package_title']) ?></h1>
    <p class="muted">📍 <?= e($b['package_location']) ?> · Reference <strong><?= e($b['booking_ref']) ?></strong></p></div>
  <div><?= status_badge($b['status']) ?> <?php if ($b['cancellation_requested'] && in_array($b['status'], ['Pending','Confirmed'], true)): ?><span class="badge badge-warn">Cancellation requested</span><?php endif; ?></div>
</div>

<div class="panel"><h3>Trip details</h3>
  <dl class="kv">
    <dt>Travel dates</dt><dd><?= e(fmt_date($b['travel_date'], 'D, d M Y')) ?> → <?= e(fmt_date($b['return_date'], 'D, d M Y')) ?></dd>
    <dt>Guests</dt><dd><?= (int) $b['guests'] ?></dd>
    <dt>Price per person</dt><dd><?= e(money($b['unit_price'])) ?></dd>
    <dt>Total</dt><dd><strong><?= e(money($b['total_amount'])) ?></strong></dd>
    <dt>Payment</dt><dd><?= $paid ? '<span class="badge badge-ok">Paid (demo)</span>' : '<span class="badge badge-muted">Unpaid</span>' ?></dd>
    <dt>Booked on</dt><dd><?= e(fmt_date($b['created_at'], 'd M Y, H:i')) ?></dd>
    <dt>Contact</dt><dd><?= e($b['contact_name']) ?> · <?= e($b['contact_email']) ?> · <?= e($b['contact_phone']) ?></dd>
    <?php if (trim((string) $b['special_requests']) !== ''): ?><dt>Special requests</dt><dd><?= nl2br(e($b['special_requests'])) ?></dd><?php endif; ?>
  </dl></div>

<div class="panel"><h3>Travellers</h3>
  <ol style="margin:0;padding-left:1.2rem"><?php foreach ($travellers as $t): ?>
    <li><?= e($t['full_name']) ?><?= $t['age'] !== null ? ' · age ' . (int) $t['age'] : '' ?><?= $t['is_lead'] ? ' <span class="badge badge-info">Lead</span>' : '' ?></li>
  <?php endforeach; ?></ol></div>

<div class="panel"><h3>Payments</h3>
  <?php if (!$payments): ?><p class="muted">No payment recorded yet.</p><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Transaction</th><th>Method</th><th>Status</th><th class="num">Amount</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($payments as $p): ?><tr><td><code><?= e($p['transaction_ref']) ?></code></td><td><?= e($p['method']) ?></td>
      <td><?= status_badge($p['status']) ?></td><td class="num"><?= e(money($p['amount'])) ?></td><td><?= e(fmt_date($p['created_at'], 'd M Y, H:i')) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
  <p class="muted small" style="margin:12px 0 0">Demonstration payments only. No real money was charged.</p></div>

<div class="panel"><h3>What would you like to do?</h3>
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
    <?php if (!$paid && in_array($b['status'], ['Pending', 'Confirmed'], true)): ?>
      <a class="btn" href="<?= e(url('checkout.php?ref=' . rawurlencode($b['booking_ref']))) ?>">Pay now (demo)</a><?php endif; ?>
    <?php if (can_review_booking($b)): ?>
      <a class="btn" href="<?= e(url('user/review.php?ref=' . rawurlencode($b['booking_ref']))) ?>">Write a review</a><?php endif; ?>
    <?php if ($review): ?><span class="muted">Your review: <?= stars((float) $review['rating']) ?> (<?= e($review['status']) ?>)</span><?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url('package_details.php?id=' . (int) $b['package_id'])) ?>">View tour</a>
  </div>
  <?php if (can_request_cancellation($b)): ?>
    <hr style="border:0;border-top:1px solid var(--line);margin:22px 0">
    <h3 style="font-size:1.05rem">Request a cancellation</h3>
    <form method="post" action="<?= e(url('user/cancel_booking.php')) ?>" data-confirm="Send a cancellation request for this booking?">
      <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($b['booking_ref']) ?>">
      <div class="form-row"><label class="lbl" for="reason">Reason <span class="muted">(optional)</span></label>
        <textarea id="reason" name="reason" maxlength="500" rows="3" placeholder="Tell us why you need to cancel"></textarea>
        <div class="hint">Our team reviews every request. Your booking stays active until it is approved.</div></div>
      <button class="btn btn-danger btn-sm" type="submit">Request cancellation</button>
    </form>
  <?php elseif ($b['cancellation_requested'] && in_array($b['status'], ['Pending','Confirmed'], true)): ?>
    <p class="muted" style="margin:18px 0 0">Cancellation requested on <?= e(fmt_date($b['cancellation_requested_at'], 'd M Y')) ?>. We'll update this booking soon.</p>
  <?php endif; ?></div>
<?php dash_close();
