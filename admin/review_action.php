<?php
require __DIR__ . '/../includes/bootstrap.php';
require_post();
require_admin();
$id = post_int('id'); $action = post_str('action', 10);
if (!db_value('SELECT COUNT(*) FROM reviews WHERE id = ?', 'i', [$id])) { abort(404, 'Review not found.'); }
if (in_array($action, ['Approved', 'Rejected'], true)) {
    db_exec('UPDATE reviews SET status = ? WHERE id = ?', 'si', [$action, $id]);
    flash('success', 'Review ' . strtolower($action) . '.');
} elseif ($action === 'delete') {
    db_exec('DELETE FROM reviews WHERE id = ?', 'i', [$id]);
    flash('success', 'Review deleted.');
} else { abort(400, 'Unknown action.'); }
redirect('admin/reviews.php');
