<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_user();
$rows = db_all(
    'SELECT y.transaction_ref, y.amount, y.method, y.status, y.created_at, b.booking_ref, p.title
       FROM payments y JOIN bookings b ON b.id = y.booking_id JOIN packages p ON p.id = b.package_id
      WHERE b.user_id = ? ORDER BY y.created_at DESC, y.id DESC', 'i', [(int) $user['id']]
);
dash_open('payments', 'Payments', $user);
?>
<h1>Payment history</h1>
<div class="demo-banner"><span>ℹ️</span><span>These are <strong>simulated</strong> payments for demonstration. No real money moves and no card details are collected or stored.</span></div>
<?php if (!$rows): ?>
  <div class="empty"><div class="big">🧾</div><h3>No payments yet</h3><p class="muted">Demo payments you make at checkout will be listed here.</p></div>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Date</th><th>Transaction</th><th>Booking</th><th>Method</th><th>Status</th><th class="num">Amount</th></tr></thead>
  <tbody><?php foreach ($rows as $r): ?>
    <tr><td><?= e(fmt_date($r['created_at'], 'd M Y, H:i')) ?></td><td><code><?= e($r['transaction_ref']) ?></code></td>
      <td><a href="<?= e(url('user/booking_view.php?ref=' . rawurlencode($r['booking_ref']))) ?>"><?= e($r['booking_ref']) ?></a><div class="muted small"><?= e($r['title']) ?></div></td>
      <td><?= e($r['method']) ?></td><td><?= status_badge($r['status']) ?></td><td class="num"><?= e(money($r['amount'])) ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif;
dash_close();
