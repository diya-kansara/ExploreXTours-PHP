<?php
require __DIR__ . '/_init.php';
$id = get_int('id');
$u = db_one('SELECT id, name, email, phone, created_at FROM users WHERE id = ?', 'i', [$id]);
if (!$u) { abort(404, 'Customer not found.'); }
$bookings = db_all('SELECT b.id, b.booking_ref, b.travel_date, b.status, b.total_amount, p.title FROM bookings b JOIN packages p ON p.id = b.package_id WHERE b.user_id = ? ORDER BY b.created_at DESC', 'i', [$id]);
$active = (int) db_value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('Pending','Confirmed') AND return_date >= CURDATE()", 'i', [$id]);
$wish = (int) db_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ?', 'i', [$id]);
$reviews = (int) db_value('SELECT COUNT(*) FROM reviews WHERE user_id = ?', 'i', [$id]);
admin_header('Customer details', 'users', $admin);
?>
<p><a href="<?= e(url('admin/manage_users.php')) ?>">← All customers</a></p>
<div class="panel"><h2><?= e($u['name']) ?></h2>
  <dl class="kv"><dt>Email</dt><dd><?= e($u['email']) ?></dd><dt>Phone</dt><dd><?= e($u['phone'] ?: '—') ?></dd>
    <dt>Joined</dt><dd><?= e(fmt_date($u['created_at'], 'd M Y, H:i')) ?></dd><dt>Saved tours</dt><dd><?= $wish ?></dd><dt>Reviews</dt><dd><?= $reviews ?></dd></dl></div>
<div class="panel"><h3>Bookings</h3>
  <?php if (!$bookings): ?><p class="muted" style="margin:0">No bookings.</p><?php else: ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Reference</th><th>Tour</th><th>Travel</th><th>Status</th><th class="num">Total</th></tr></thead><tbody>
  <?php foreach ($bookings as $b): ?><tr><td><a href="<?= e(url('admin/booking_view.php?id=' . (int) $b['id'])) ?>"><?= e($b['booking_ref']) ?></a></td><td><?= e($b['title']) ?></td>
    <td><?= e(fmt_date($b['travel_date'])) ?></td><td><?= status_badge($b['status']) ?></td><td class="num"><?= e(money($b['total_amount'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<div class="panel"><h3>Delete account</h3>
  <?php if ($active > 0): ?><p class="muted" style="margin:0">This customer has <?= $active ?> active upcoming booking<?= $active > 1 ? 's' : '' ?>, so the account can't be deleted. Cancel or complete them first.</p>
  <?php else: ?><p class="muted">Deleting removes the account, wishlist and reviews. Past bookings are kept for records (without an account link).</p>
    <?= admin_action_form(url('admin/user_action.php'), ['id' => $id, 'action' => 'delete'], 'Delete customer', 'btn btn-sm btn-danger', 'Delete ' . $u['name'] . '? This cannot be undone.') ?><?php endif; ?></div>
<?php admin_footer();
