<?php
require __DIR__ . '/_init.php';
$status = get_str('status', 10); if (!in_array($status, ['Pending', 'Approved', 'Rejected'], true)) { $status = ''; }
$w = $status !== '' ? 'WHERE r.status = ?' : ''; $types = $status !== '' ? 's' : ''; $params = $status !== '' ? [$status] : [];
$rows = db_all("SELECT r.id, r.rating, r.title, r.comment, r.status, r.created_at, u.name, p.title AS package_title
                  FROM reviews r JOIN users u ON u.id = r.user_id JOIN packages p ON p.id = r.package_id $w ORDER BY (r.status = 'Pending') DESC, r.created_at DESC LIMIT 100", $types, $params);
admin_header('Review moderation', 'reviews', $admin);
?>
<div class="cat-row" style="margin-bottom:20px"><?php foreach (['' => 'All', 'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'] as $k => $l): ?>
  <a class="cat-pill <?= $status === $k ? 'active' : '' ?>" href="<?= e(url('admin/reviews.php' . ($k ? '?status=' . $k : ''))) ?>"><?= e($l) ?></a><?php endforeach; ?></div>
<?php if (!$rows): ?><div class="empty"><h3>No reviews here</h3><p class="muted">Reviews appear after customers complete a trip.</p></div><?php endif; ?>
<?php foreach ($rows as $r): ?><div class="panel" style="margin-bottom:14px">
  <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><div><?= stars((float) $r['rating']) ?> <strong><?= e($r['title'] ?: '') ?></strong>
    <div class="muted small"><?= e($r['name']) ?> on <em><?= e($r['package_title']) ?></em> · <?= e(fmt_date($r['created_at'])) ?></div></div><div><?= status_badge($r['status']) ?></div></div>
  <p style="margin:12px 0"><?= nl2br(e($r['comment'])) ?></p>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <?php if ($r['status'] !== 'Approved') echo admin_action_form(url('admin/review_action.php'), ['id' => $r['id'], 'action' => 'Approved'], 'Approve', 'btn btn-sm'); ?>
    <?php if ($r['status'] !== 'Rejected') echo admin_action_form(url('admin/review_action.php'), ['id' => $r['id'], 'action' => 'Rejected'], 'Reject', 'btn btn-sm btn-outline'); ?>
    <?= admin_action_form(url('admin/review_action.php'), ['id' => $r['id'], 'action' => 'delete'], 'Delete', 'btn btn-sm btn-ghost', 'Delete this review permanently?') ?></div></div><?php endforeach; ?>
<?php admin_footer();
