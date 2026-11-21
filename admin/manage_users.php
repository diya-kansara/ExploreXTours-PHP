<?php
require __DIR__ . '/_init.php';
$q = get_str('q', 80); $page = max(1, get_int('page', 1)); $per = 20;
$w = '1=1'; $types = ''; $params = [];
if ($q !== '') { $like = '%' . addcslashes($q, '%_\\') . '%'; $w = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; $types = 'sss'; $params = [$like, $like, $like]; }
$total = (int) db_value("SELECT COUNT(*) FROM users u WHERE $w", $types, $params);
$pages = max(1, (int) ceil($total / $per)); $page = min($page, $pages);
$rows = db_all("SELECT u.id, u.name, u.email, u.phone, u.created_at,
        (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS bookings,
        (SELECT COALESCE(SUM(b.total_amount),0) FROM bookings b WHERE b.user_id = u.id AND b.status IN ('Confirmed','Completed')) AS spent
      FROM users u WHERE $w ORDER BY u.created_at DESC, u.id DESC LIMIT ? OFFSET ?", $types . 'ii', array_merge($params, [$per, ($page - 1) * $per]));
admin_header('Customers', 'users', $admin);
?>
<form class="toolbar" method="get"><div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or phone"></div>
  <button class="btn btn-sm" type="submit">Search</button><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/manage_users.php')) ?>">Clear</a></form>
<p class="muted small"><?= (int) $total ?> customer<?= $total === 1 ? '' : 's' ?></p>
<div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th>Bookings</th><th class="num">Booked value</th><th></th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="center muted" style="padding:32px">No customers found.</td></tr><?php endif; ?>
<?php foreach ($rows as $u): ?><tr><td><strong><?= e($u['name']) ?></strong></td><td><?= e($u['email']) ?></td><td><?= e($u['phone'] ?: '—') ?></td>
  <td><?= e(fmt_date($u['created_at'])) ?></td><td><?= (int) $u['bookings'] ?></td><td class="num"><?= e(money($u['spent'])) ?></td>
  <td class="actions"><a class="btn btn-sm btn-outline" href="<?= e(url('admin/user_view.php?id=' . (int) $u['id'])) ?>">View</a></td></tr><?php endforeach; ?></tbody></table></div>
<?= pager($page, $pages, 'admin/manage_users.php', ['q' => $q]) ?>
<?php admin_footer();
