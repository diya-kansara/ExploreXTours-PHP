<?php
require __DIR__ . '/_init.php';
$filter = get_str('show', 6) === 'unread' ? 'unread' : 'all';
$rows = db_all('SELECT id, name, email, subject, message, is_read, created_at FROM contacts ' . ($filter === 'unread' ? 'WHERE is_read = 0 ' : '') . 'ORDER BY created_at DESC, id DESC LIMIT 200');
admin_header('Contact messages', 'messages', $admin);
?>
<div class="cat-row" style="margin-bottom:20px"><a class="cat-pill <?= $filter === 'all' ? 'active' : '' ?>" href="<?= e(url('admin/view_messages.php')) ?>">All</a>
  <a class="cat-pill <?= $filter === 'unread' ? 'active' : '' ?>" href="<?= e(url('admin/view_messages.php?show=unread')) ?>">Unread</a></div>
<?php if (!$rows): ?><div class="empty"><h3>No messages</h3></div><?php endif; ?>
<?php foreach ($rows as $m): ?><details class="msg <?= $m['is_read'] ? '' : 'unread' ?>"><summary>
  <strong><?= e($m['subject']) ?></strong><span class="muted small">from <?= e($m['name']) ?></span><span class="muted small" style="margin-left:auto"><?= e(fmt_date($m['created_at'], 'd M Y, H:i')) ?></span></summary>
  <div class="body"><p class="muted small" style="margin:12px 0"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p><p><?= nl2br(e($m['message'])) ?></p>
    <div style="display:flex;gap:8px"><?= admin_action_form(url('admin/message_action.php'), ['id' => $m['id'], 'action' => $m['is_read'] ? 'unread' : 'read'], $m['is_read'] ? 'Mark as unread' : 'Mark as read', 'btn btn-sm btn-outline') ?>
    <?= admin_action_form(url('admin/message_action.php'), ['id' => $m['id'], 'action' => 'delete'], 'Delete', 'btn btn-sm btn-ghost', 'Delete this message?') ?></div></div></details><?php endforeach; ?>
<?php admin_footer();
