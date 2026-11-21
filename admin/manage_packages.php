<?php
require __DIR__ . '/_init.php';
$q = get_str('q', 80); $cat = get_int('category'); $state = get_str('state', 8);
$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') { $like = '%' . addcslashes($q, '%_\\') . '%'; $where[] = '(p.title LIKE ? OR p.location LIKE ?)'; $types .= 'ss'; array_push($params, $like, $like); }
if ($cat > 0) { $where[] = 'p.category_id = ?'; $types .= 'i'; $params[] = $cat; }
if ($state === 'active') { $where[] = 'p.is_active = 1'; } elseif ($state === 'hidden') { $where[] = 'p.is_active = 0'; }
$rows = db_all(
    'SELECT p.id, p.title, p.location, p.price, p.duration, p.image, p.is_active, p.is_featured, c.name AS category,
            (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.id) AS bookings
       FROM packages p LEFT JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC', $types, $params
);
$cats = db_all('SELECT id, name FROM categories ORDER BY sort_order');
admin_header('Packages', 'packages', $admin);
?>
<form class="toolbar" method="get">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Title or destination"></div>
  <div class="f"><label for="c">Category</label><select id="c" name="category"><option value="">All</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="s">Visibility</label><select id="s" name="state"><option value="">All</option><option value="active" <?= $state === 'active' ? 'selected' : '' ?>>Visible</option><option value="hidden" <?= $state === 'hidden' ? 'selected' : '' ?>>Hidden</option></select></div>
  <button class="btn btn-sm" type="submit">Filter</button><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/manage_packages.php')) ?>">Clear</a>
  <a class="btn btn-sm" style="margin-left:auto" href="<?= e(url('admin/add_package.php')) ?>">+ Add package</a>
</form>
<div class="table-wrap"><table class="table"><thead><tr><th></th><th>Package</th><th>Category</th><th class="num">Price</th><th>Bookings</th><th>Status</th><th></th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="7" class="center muted" style="padding:32px">No packages found.</td></tr><?php endif; ?>
<?php foreach ($rows as $p): ?><tr>
  <td><img class="thumb" src="<?= e(pkg_image_url($p['image'])) ?>" alt=""></td>
  <td class="wrap"><strong><?= e($p['title']) ?></strong><div class="muted small"><?= e($p['location']) ?> · <?= e($p['duration']) ?></div></td>
  <td><?= e($p['category'] ?? '-') ?></td><td class="num"><?= e(money($p['price'])) ?></td><td><?= (int) $p['bookings'] ?></td>
  <td><?= $p['is_active'] ? '<span class="badge badge-ok">Visible</span>' : '<span class="badge badge-muted">Hidden</span>' ?><?= $p['is_featured'] ? ' <span class="badge badge-info">Featured</span>' : '' ?></td>
  <td class="actions"><div class="row-actions">
    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/edit_package.php?id=' . (int) $p['id'])) ?>">Edit</a>
    <?= admin_action_form(url('admin/package_action.php'), ['id' => $p['id'], 'action' => 'toggle'], $p['is_active'] ? 'Hide' : 'Show', 'btn btn-sm btn-ghost') ?>
    <?= admin_action_form(url('admin/package_action.php'), ['id' => $p['id'], 'action' => 'delete'], 'Delete', 'btn btn-sm btn-ghost', 'Delete “' . $p['title'] . '” permanently? This cannot be undone.') ?></div></td>
</tr><?php endforeach; ?></tbody></table></div>
<?php admin_footer();
