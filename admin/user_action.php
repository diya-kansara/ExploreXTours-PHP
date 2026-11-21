<?php
require __DIR__ . '/../includes/bootstrap.php';
require_post();
require_admin();
$id = post_int('id');
$u = db_one('SELECT id, name FROM users WHERE id = ?', 'i', [$id]);
if (!$u) { abort(404, 'Customer not found.'); }
if (post_str('action', 10) !== 'delete') { abort(400, 'Unknown action.'); }
$active = (int) db_value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('Pending','Confirmed') AND return_date >= CURDATE()", 'i', [$id]);
if ($active > 0) {
    flash('error', $u['name'] . ' has active upcoming bookings and cannot be deleted.');
    redirect('admin/user_view.php?id=' . $id);
}
db_exec('DELETE FROM users WHERE id = ?', 'i', [$id]);      // wishlist + reviews cascade; bookings.user_id -> NULL
flash('success', 'Customer ' . $u['name'] . ' was deleted.');
redirect('admin/manage_users.php');
