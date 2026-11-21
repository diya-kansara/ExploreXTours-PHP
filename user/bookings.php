<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_user();
$uid = (int) $user['id'];

$filter = get_str('status', 12);
$valid = array_merge(['all'], BOOKING_STATUSES);
if (!in_array($filter, $valid, true)) { $filter = 'all'; }

$sql = "SELECT b.id, b.booking_ref, b.travel_date, b.guests, b.status, b.total_amount, b.created_at, b.cancellation_requested,
               p.title, p.location,
               (SELECT COUNT(*) FROM payments y WHERE y.booking_id = b.id AND y.status = 'Success') AS paid
          FROM bookings b JOIN packages p ON p.id = b.package_id WHERE b.user_id = ?";
$types = 'i'; $params = [$uid];
if ($filter !== 'all') { $sql .= ' AND b.status = ?'; $types .= 's'; $params[] = $filter; }
$rows = db_all($sql . ' ORDER BY b.created_at DESC', $types, $params);

dash_open('bookings', 'My bookings', $user);
?>
<h1>Booking history</h1>
<div class="cat-row" style="margin:14px 0 24px">
  <?php foreach ($valid as $s): ?>
    <a class="cat-pill <?= $filter === $s ? 'active' : '' ?>" href="<?= e(url('user/bookings.php' . ($s === 'all' ? '' : '?status=' . $s))) ?>"><?= e(ucfirst($s)) ?></a>
  <?php endforeach; ?>
</div>
<?php if (!$rows): ?>
  <div class="empty"><div class="big">🗂️</div><h3>No bookings <?= $filter === 'all' ? 'yet' : 'with this status' ?></h3>
    <a class="btn" href="<?= e(url('packages.php')) ?>">Browse tours</a></div>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Reference</th><th>Tour</th><th>Travel date</th><th>Guests</th><th>Status</th><th>Payment</th><th class="num">Total</th><th></th></tr></thead>
  <tbody><?php foreach ($rows as $b): ?>
    <tr><td><strong><?= e($b['booking_ref']) ?></strong></td>
      <td><?= e($b['title']) ?><div class="muted small"><?= e($b['location']) ?></div></td>
      <td><?= e(fmt_date($b['travel_date'])) ?></td><td><?= (int) $b['guests'] ?></td>
      <td><?= status_badge($b['status']) ?><?php if ($b['cancellation_requested'] && in_array($b['status'], ['Pending','Confirmed'], true)): ?><div class="small muted">Cancellation requested</div><?php endif; ?></td>
      <td><?= (int) $b['paid'] ? '<span class="badge badge-ok">Paid (demo)</span>' : '<span class="badge badge-muted">Unpaid</span>' ?></td>
      <td class="num"><?= e(money($b['total_amount'])) ?></td>
      <td class="actions"><a class="btn btn-sm btn-outline" href="<?= e(url('user/booking_view.php?ref=' . rawurlencode($b['booking_ref']))) ?>">Details</a></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif;
dash_close();
