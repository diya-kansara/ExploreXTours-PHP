<?php
require __DIR__ . '/../includes/bootstrap.php';
require_post();
require_admin();
$id = post_int('id'); $action = post_str('action', 10);
if (!db_value('SELECT COUNT(*) FROM contacts WHERE id = ?', 'i', [$id])) { abort(404, 'Message not found.'); }
if ($action === 'read' || $action === 'unread') {
    db_exec('UPDATE contacts SET is_read = ? WHERE id = ?', 'ii', [$action === 'read' ? 1 : 0, $id]);
} elseif ($action === 'delete') {
    db_exec('DELETE FROM contacts WHERE id = ?', 'i', [$id]);
    flash('success', 'Message deleted.');
} else { abort(400, 'Unknown action.'); }
redirect('admin/view_messages.php');
