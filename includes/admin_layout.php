<?php
/** Admin panel chrome: sidebar + top bar. Pages call admin_header() / admin_footer(). */
declare(strict_types=1);

function admin_counts(): array
{
    return [
        'bookings' => (int) db_value("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'"),
        'cancel'   => (int) db_value("SELECT COUNT(*) FROM bookings WHERE cancellation_requested = 1 AND status IN ('Pending','Confirmed')"),
        'reviews'  => (int) db_value("SELECT COUNT(*) FROM reviews WHERE status = 'Pending'"),
        'messages' => (int) db_value('SELECT COUNT(*) FROM contacts WHERE is_read = 0'),
    ];
}

function admin_header(string $title, string $active, array $admin, array $opts = []): void
{
    $c = admin_counts();
    $nav = [
        ['dashboard',  'admin/dashboard.php',        'Dashboard',  '▦', 0],
        ['packages',   'admin/manage_packages.php',  'Packages',   '✈', 0],
        ['bookings',   'admin/manage_bookings.php',  'Bookings',   '🗓', $c['bookings'] + $c['cancel']],
        ['payments',   'admin/payments.php',         'Payments',   '₹', 0],
        ['users',      'admin/manage_users.php',     'Customers',  '☺', 0],
        ['reviews',    'admin/reviews.php',          'Reviews',    '★', $c['reviews']],
        ['messages',   'admin/view_messages.php',    'Messages',   '✉', $c['messages']],
    ];
    if ($admin['role'] === 'super_admin') {
        $nav[] = ['admins', 'admin/admins.php', 'Administrators', '🔑', 0];
    }
    $nav[] = ['profile', 'admin/profile.php', 'My profile', '⚙', 0];
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> · ExploreX Admin</title>
  <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
</head>
<body class="admin">
<a class="skip-link" href="#main">Skip to content</a>
<div class="admin-shell">
  <aside class="admin-side" id="admin-side">
    <a class="brand brand-light" href="<?= e(url('admin/dashboard.php')) ?>"><?= brand_mark() ?><span>Explore<b>X</b></span></a>
    <p class="side-label">Admin panel</p>
    <nav aria-label="Admin">
      <?php foreach ($nav as [$key, $file, $label, $icon, $badge]): ?>
        <a href="<?= e(url($file)) ?>"<?= $key === $active ? ' class="active" aria-current="page"' : '' ?>>
          <span class="ic" aria-hidden="true"><?= $icon ?></span><span><?= e($label) ?></span>
          <?php if ($badge > 0): ?><span class="pill"><?= (int) $badge ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View website ↗</a>
      <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button type="submit">Log out</button></form>
    </div>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <button class="side-toggle" type="button" aria-controls="admin-side" aria-expanded="false" aria-label="Toggle navigation">☰</button>
      <h1><?= e($title) ?></h1>
      <div class="who"><span class="avatar"><?= e(mb_strtoupper(mb_substr($admin['name'], 0, 1))) ?></span>
        <span><strong><?= e($admin['name']) ?></strong><small><?= $admin['role'] === 'super_admin' ? 'Super admin' : 'Administrator' ?></small></span></div>
    </header>
    <main id="main" class="admin-content">
      <?php foreach (flash_pull() as $f): ?><div class="flash flash-<?= e($f['type']) ?>" role="status" style="margin:0 0 18px"><?= e($f['message']) ?></div><?php endforeach; ?>
<?php
}

function admin_footer(array $opts = []): void
{
    ?>
    </main>
  </div>
</div>
<?php if (!empty($opts['charts'])): ?><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script><?php endif; ?>
<script src="<?= e(url('assets/js/admin.js')) ?>"></script>
</body>
</html>
<?php
}

/** Small POST form that carries one action (used for confirm / cancel / delete buttons). */
function admin_action_form(string $action, array $fields, string $label, string $class = 'btn btn-sm btn-outline', ?string $confirm = null): string
{
    $html = '<form class="inline-form" method="post" action="' . e($action) . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field();
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html . '<button class="' . e($class) . '" type="submit">' . e($label) . '</button></form>';
}

function pager(int $page, int $pages, string $base, array $params): string
{
    if ($pages <= 1) {
        return '';
    }
    $link = fn(int $p) => url($base . '?' . http_build_query(array_filter($params + ['page' => $p], fn($v) => $v !== '' && $v !== null)));
    $out = '<nav class="pagination" aria-label="Pagination">';
    if ($page > 1) $out .= '<a href="' . e($link($page - 1)) . '">‹</a>';
    for ($i = 1; $i <= $pages; $i++) {
        $out .= $i === $page ? '<span class="current">' . $i . '</span>' : '<a href="' . e($link($i)) . '">' . $i . '</a>';
    }
    if ($page < $pages) $out .= '<a href="' . e($link($page + 1)) . '">›</a>';
    return $out . '</nav>';
}
