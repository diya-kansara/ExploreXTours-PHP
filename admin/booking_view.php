<?php
require __DIR__ . '/_init.php';
$id = get_int('id');
$b = db_one(
    'SELECT b.*, p.title, p.location, u.name AS user_name, u.email AS user_email
       FROM bookings b JOIN packages p ON p.id = b.package_id LEFT JOIN users u ON u.id = b.user_id WHERE b.id = ?', 'i', [$id]
);
if (!$b) { abort(404, 'Booking not found.'); }
$travellers = db_all('SELECT full_name, age, is_lead FROM booking_travellers WHERE booking_id = ? ORDER BY is_lead DESC, id', 'i', [$id]);
$payments = db_all('SELECT transaction_ref, method, status, amount, failure_reason, created_at FROM payments WHERE booking_id = ? ORDER BY id DESC', 'i', [$id]);
$next = allowed_status_changes($b['status']);

admin_header('Booking ' . $b['booking_ref'], 'bookings', $admin);
?>
<p><a href="<?= e(url('admin/manage_bookings.php')) ?>">← All bookings</a></p>
<?php if ($b['cancellation_requested'] && in_array($b['status'], ['Pending','Confirmed'], true)): ?>
  <div class="alert alert-warn" style="margin-bottom:20px"><strong>Cancellation requested</strong> on <?= e(fmt_date($b['cancellation_requested_at'], 'd M Y, H:i')) ?>.
    <?= trim((string) $b['cancellation_reason']) !== '' ? 'Reason: “' . e($b['cancellation_reason']) . '”' : 'No reason given.' ?>
    <div style="margin-top:10px;display:flex;gap:8px">
      <?= admin_action_form(url('admin/booking_action.php'), ['id' => $id, 'action' => 'status', 'to' => 'Cancelled'], 'Approve &amp; cancel booking', 'btn btn-sm btn-danger', 'Cancel this booking?') ?>
      <?= admin_action_form(url('admin/booking_action.php'), ['id' => $id, 'action' => 'reject_cancel'], 'Decline request', 'btn btn-sm btn-outline') ?></div></div>
<?php endif; ?>
<div class="panel"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div><h2 style="margin-bottom:2px"><?= e($b['title']) ?></h2><p class="muted" style="margin:0">📍 <?= e($b['location']) ?> · <strong><?= e($b['booking_ref']) ?></strong></p></div>
  <div><?= status_badge($b['status']) ?></div></div>
  <dl class="kv" style="margin-top:20px">
    <dt>Travel dates</dt><dd><?= e(fmt_date($b['travel_date'], 'D, d M Y')) ?> → <?= e(fmt_date($b['return_date'], 'D, d M Y')) ?></dd>
    <dt>Guests</dt><dd><?= (int) $b['guests'] ?></dd>
    <dt>Unit price</dt><dd><?= e(money($b['unit_price'])) ?></dd>
    <dt>Total</dt><dd><strong><?= e(money($b['total_amount'])) ?></strong></dd>
    <dt>Contact</dt><dd><?= e($b['contact_name']) ?> · <?= e($b['contact_email']) ?> · <?= e($b['contact_phone']) ?></dd>
    <dt>Account</dt><dd><?= $b['user_id'] ? '<a href="' . e(url('admin/user_view.php?id=' . (int) $b['user_id'])) . '">' . e($b['user_name']) . '</a> (' . e($b['user_email']) . ')' : '<span class="muted">Account removed</span>' ?></dd>
    <dt>Created</dt><dd><?= e(fmt_date($b['created_at'], 'd M Y, H:i')) ?></dd>
    <?php if (trim((string) $b['special_requests']) !== ''): ?><dt>Special requests</dt><dd><?= nl2br(e($b['special_requests'])) ?></dd><?php endif; ?>
  </dl></div>
<div class="panel"><h3>Update status</h3>
  <?php if (!$next): ?><p class="muted" style="margin:0">This booking is <?= e(strtolower($b['status'])) ?> and its status is final.</p>
  <?php else: ?><div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php foreach ($next as $to): echo admin_action_form(url('admin/booking_action.php'), ['id' => $id, 'action' => 'status', 'to' => $to], 'Mark as ' . $to, $to === 'Cancelled' ? 'btn btn-sm btn-danger' : 'btn btn-sm', $to === 'Cancelled' ? 'Cancel this booking?' : null); endforeach; ?></div>
  <p class="muted small" style="margin:10px 0 0">“Completed” is only allowed once the return date has passed. Completed trips unlock customer reviews.</p><?php endif; ?></div>
<div class="panel"><h3>Travellers</h3><ol style="margin:0;padding-left:1.2rem"><?php foreach ($travellers as $t): ?>
  <li><?= e($t['full_name']) ?><?= $t['age'] !== null ? ' · ' . (int) $t['age'] : '' ?><?= $t['is_lead'] ? ' <span class="badge badge-info">Lead</span>' : '' ?></li><?php endforeach; ?></ol></div>
<div class="panel"><h3>Simulated payments</h3>
  <?php if (!$payments): ?><p class="muted" style="margin:0">No payment attempts.</p><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Transaction</th><th>Method</th><th>Status</th><th class="num">Amount</th><th>Date</th></tr></thead><tbody>
  <?php foreach ($payments as $p): ?><tr><td><code><?= e($p['transaction_ref']) ?></code></td><td><?= e($p['method']) ?></td><td><?= status_badge($p['status']) ?></td>
    <td class="num"><?= e(money($p['amount'])) ?></td><td><?= e(fmt_date($p['created_at'], 'd M Y, H:i')) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
  <p class="muted small" style="margin:10px 0 0">Demonstration data only. No real transactions.</p></div>
<?php admin_footer();
