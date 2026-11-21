<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_user();
$uid = (int) $user['id'];

$rows = db_all(PACKAGE_SELECT . ' JOIN wishlist w ON w.package_id = p.id WHERE w.user_id = ? AND p.is_active = 1 ORDER BY w.created_at DESC', 'i', [$uid]);
$wishIds = array_map(fn($r) => (int) $r['id'], $rows);

dash_open('wishlist', 'Saved tours', $user);
?>
<h1>Saved tours</h1>
<p class="muted">Tours you've bookmarked for later.</p>
<?php if (!$rows): ?>
  <div class="empty"><div class="big">♡</div><h3>Nothing saved yet</h3><p class="muted">Tap the heart on any tour to keep it here.</p>
    <a class="btn" href="<?= e(url('packages.php')) ?>">Browse tours</a></div>
<?php else: ?>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
    <?php foreach ($rows as $p) package_card($p, $user, $wishIds); ?>
  </div>
<?php endif;
dash_close();
