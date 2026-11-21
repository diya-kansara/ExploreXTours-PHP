<?php
/**
 * Shared page chrome for the public site and customer area.
 * Admin pages use includes/admin_layout.php.
 */
declare(strict_types=1);

function brand_mark(): string
{
    return '<svg class="brand-mark" viewBox="0 0 32 32" width="30" height="30" aria-hidden="true" focusable="false">'
         . '<circle cx="16" cy="16" r="14" fill="none" stroke="currentColor" stroke-width="1.6"/>'
         . '<path d="M16 5l3.2 8.8L28 16l-8.8 2.2L16 27l-3.2-8.8L4 16l8.8-2.2z" fill="currentColor" opacity=".92"/>'
         . '</svg>';
}

function nav_link(string $file, string $label, string $active): string
{
    $on = $active === $file ? ' class="active" aria-current="page"' : '';
    return '<a href="' . e(url($file)) . '"' . $on . '>' . e($label) . '</a>';
}

function page_header(string $title, array $opts = []): void
{
    $active   = $opts['active'] ?? '';
    $desc     = $opts['description'] ?? 'ExploreX Tours - curated tour packages across India and the world. Browse, book and track your trips in one place.';
    $bodyCls  = $opts['body_class'] ?? '';
    $user     = current_user();
    $admin    = $user ? null : current_admin();
    $wishCnt  = $user ? (int) db_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ?', 'i', [(int) $user['id']]) : 0;
    $fullTitle = $title === 'Home' ? 'ExploreX Tours - Journeys, thoughtfully planned' : $title . ' · ExploreX Tours';
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <meta name="theme-color" content="#1f3d2b">
  <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="base-path" content="<?= e(base_path()) ?>">
</head>
<body class="<?= e($bodyCls) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" id="top">
  <div class="container nav-row">
    <a class="brand" href="<?= e(url('index.php')) ?>"><?= brand_mark() ?><span>Explore<b>X</b></span></a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Toggle menu"><span></span><span></span><span></span></button>
    <nav class="site-nav" id="site-nav" aria-label="Main">
      <?= nav_link('packages.php', 'Tours', $active) ?>
      <a href="<?= e(url('index.php#destinations')) ?>">Destinations</a>
      <?= nav_link('about.php', 'About', $active) ?>
      <?= nav_link('contact.php', 'Contact', $active) ?>
      <span class="nav-sep" aria-hidden="true"></span>
      <?php if ($user): ?>
        <a class="nav-icon<?= $active === 'wishlist' ? ' active' : '' ?>" href="<?= e(url('user/wishlist.php')) ?>" title="Saved tours">♡ <span class="count" data-wishlist-count><?= $wishCnt ?></span><span class="sr-only"> saved tours</span></a>
        <details class="user-menu">
          <summary><span class="avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span class="user-name"><?= e(explode(' ', $user['name'])[0]) ?></span></summary>
          <div class="menu-pop">
            <a href="<?= e(url('user/dashboard.php')) ?>">Overview</a>
            <a href="<?= e(url('user/bookings.php')) ?>">My bookings</a>
            <a href="<?= e(url('user/wishlist.php')) ?>">Saved tours</a>
            <a href="<?= e(url('user/payments.php')) ?>">Payments</a>
            <a href="<?= e(url('user/profile.php')) ?>">Profile</a>
            <form method="post" action="<?= e(url('user/logout.php')) ?>"><?= csrf_field() ?><button type="submit">Log out</button></form>
          </div>
        </details>
      <?php elseif ($admin): ?>
        <a class="btn btn-sm btn-outline" href="<?= e(url('admin/dashboard.php')) ?>">Admin panel</a>
      <?php else: ?>
        <a href="<?= e(url('user/login.php')) ?>">Log in</a>
        <a class="btn btn-sm" href="<?= e(url('user/register.php')) ?>">Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php foreach (flash_pull() as $f): ?>
  <div class="container"><div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div></div>
<?php endforeach; ?>
<main id="main">
<?php
}

function page_footer(): void
{
    ?>
</main>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="brand brand-light" href="<?= e(url('index.php')) ?>"><?= brand_mark() ?><span>Explore<b>X</b></span></a>
      <p class="footer-about">Thoughtfully planned journeys across India and the world, with clear pricing and a real person on the other end.</p>
    </div>
    <div>
      <h4>Explore</h4>
      <a href="<?= e(url('packages.php')) ?>">All tours</a>
      <a href="<?= e(url('packages.php?sort=popular')) ?>">Most loved</a>
      <a href="<?= e(url('about.php')) ?>">About us</a>
      <a href="<?= e(url('contact.php')) ?>">Contact</a>
    </div>
    <div>
      <h4>Your account</h4>
      <a href="<?= e(url('user/login.php')) ?>">Log in</a>
      <a href="<?= e(url('user/register.php')) ?>">Create account</a>
      <a href="<?= e(url('user/bookings.php')) ?>">My bookings</a>
      <a href="<?= e(url('user/wishlist.php')) ?>">Saved tours</a>
    </div>
    <div>
      <h4>Travel notes</h4>
      <form class="newsletter" method="post" action="<?= e(url('newsletter.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="back" value="<?= e(current_path()) ?>">
        <label class="sr-only" for="nl-email">Email address</label>
        <input id="nl-email" type="email" name="email" placeholder="you@example.com" required maxlength="150" autocomplete="email">
        <button type="submit" class="btn btn-sm btn-light">Subscribe</button>
      </form>
      <p class="small footer-note">One short email a month. Unsubscribe any time.</p>
    </div>
  </div>
  <div class="container footer-base">
    <span>&copy; <?= date('Y') ?> ExploreX Tours. Academic portfolio project.</span>
    <span>Payments on this site are a <strong>demonstration only</strong>. No real money or card data is processed.</span>
  </div>
</footer>
<script src="<?= e(url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

/** Render an error page and stop. */
function abort(int $code, string $message = ''): never
{
    $titles = [403 => 'Not allowed', 404 => 'Page not found', 405 => 'Method not allowed', 419 => 'Request expired'];
    http_response_code($code);
    $title = $titles[$code] ?? 'Error';
    page_header($title);
    echo '<section class="section"><div class="container narrow center">'
       . '<p class="eyebrow">Error ' . (int) $code . '</p><h1>' . e($title) . '</h1>'
       . '<p class="lead">' . e($message ?: 'We could not find what you were looking for.') . '</p>'
       . '<p><a class="btn" href="' . e(url('index.php')) . '">Back to home</a></p></div></section>';
    page_footer();
    exit;
}

/** Small reusable package card. $p needs: id,title,location,price,image,duration + optional avg_rating,review_count,category_name,in_wishlist */
function package_card(array $p, ?array $user = null, array $wishIds = []): void
{
    $inWish = in_array((int) $p['id'], $wishIds, true);
    ?>
    <article class="card pkg-card">
      <a class="card-media" href="<?= e(url('package_details.php?id=' . (int) $p['id'])) ?>">
        <img src="<?= e(pkg_image_url($p['image'])) ?>" alt="<?= e($p['title']) ?> - <?= e($p['location']) ?>" loading="lazy" width="640" height="440">
        <?php if (!empty($p['category_name'])): ?><span class="chip"><?= e($p['category_name']) ?></span><?php endif; ?>
      </a>
      <form class="wish-form" method="post" action="<?= e(url('wishlist_toggle.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="package_id" value="<?= (int) $p['id'] ?>">
        <input type="hidden" name="back" value="<?= e(current_path()) ?>">
        <button type="submit" class="wish-btn<?= $inWish ? ' is-saved' : '' ?>" data-wish aria-pressed="<?= $inWish ? 'true' : 'false' ?>" title="<?= $inWish ? 'Remove from saved tours' : 'Save this tour' ?>"><span aria-hidden="true">♥</span><span class="sr-only"><?= $inWish ? 'Remove from saved tours' : 'Save this tour' ?></span></button>
      </form>
      <div class="card-body">
        <p class="card-loc">📍 <?= e($p['location']) ?> · <?= e($p['duration'] ?: '') ?></p>
        <h3 class="card-title"><a href="<?= e(url('package_details.php?id=' . (int) $p['id'])) ?>"><?= e($p['title']) ?></a></h3>
        <p class="card-text"><?= e(mb_strimwidth((string) $p['description'], 0, 90, '…')) ?></p>
        <div class="card-foot">
          <div class="price"><small>from</small> <?= e(money($p['price'])) ?><small> / person</small></div>
          <?php if (!empty($p['review_count'])): ?>
            <div class="rating"><?= stars((float) $p['avg_rating']) ?> <small>(<?= (int) $p['review_count'] ?>)</small></div>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php
}

function wishlist_ids(?array $user): array
{
    if (!$user) {
        return [];
    }
    return array_map('intval', array_column(db_all('SELECT package_id FROM wishlist WHERE user_id = ?', 'i', [(int) $user['id']]), 'package_id'));
}

/** Package rows with rating + category (used by home, listing, related). */
const PACKAGE_SELECT = 'SELECT p.id, p.title, p.location, p.description, p.price, p.image, p.duration, p.duration_days,
        p.is_featured, p.max_guests, c.name AS category_name, c.slug AS category_slug,
        COALESCE(r.avg_rating, 0) AS avg_rating, COALESCE(r.review_count, 0) AS review_count,
        COALESCE(b.booking_count, 0) AS booking_count
     FROM packages p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN (SELECT package_id, ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count
                  FROM reviews WHERE status = \'Approved\' GROUP BY package_id) r ON r.package_id = p.id
     LEFT JOIN (SELECT package_id, COUNT(*) AS booking_count
                  FROM bookings WHERE status IN (\'Confirmed\',\'Completed\') GROUP BY package_id) b ON b.package_id = p.id';

// ---------------------------------------------------------------------------
// Customer dashboard chrome
// ---------------------------------------------------------------------------
function dash_open(string $active, string $title, array $user): void
{
    $uid = (int) $user['id'];
    $counts = [
        'bookings' => (int) db_value('SELECT COUNT(*) FROM bookings WHERE user_id = ?', 'i', [$uid]),
        'wishlist' => (int) db_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ?', 'i', [$uid]),
    ];
    page_header($title, ['active' => $active === 'wishlist' ? 'wishlist' : 'dash']);
    $items = [
        'overview' => ['user/dashboard.php', 'Overview', null],
        'bookings' => ['user/bookings.php', 'My bookings', $counts['bookings']],
        'wishlist' => ['user/wishlist.php', 'Saved tours', $counts['wishlist']],
        'payments' => ['user/payments.php', 'Payments', null],
        'profile'  => ['user/profile.php', 'Profile', null],
    ];
    echo '<div class="container dash"><nav class="dash-nav" aria-label="Account">';
    foreach ($items as $key => [$file, $label, $n]) {
        echo '<a href="' . e(url($file)) . '"' . ($key === $active ? ' class="active" aria-current="page"' : '') . '><span>' . e($label) . '</span>'
           . ($n !== null ? '<span class="pill">' . (int) $n . '</span>' : '') . '</a>';
    }
    echo '</nav><div class="dash-main">';
}

function dash_close(): void
{
    echo '</div></div>';
    page_footer();
}

function render_errors(array $errors): void
{
    if (!$errors) {
        return;
    }
    echo '<div class="alert alert-error" role="alert"><ul>';
    foreach ($errors as $m) {
        echo '<li>' . e($m) . '</li>';
    }
    echo '</ul></div><br>';
}

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="field-error">' . e($errors[$key]) . '</div>' : '';
}

function booking_steps(int $current): void
{
    $steps = ['Trip details', 'Review', 'Payment', 'Confirmed'];
    echo '<ol class="steps" aria-label="Booking progress">';
    foreach ($steps as $i => $label) {
        $n = $i + 1;
        $cls = $n < $current ? 'done' : ($n === $current ? 'on' : '');
        echo '<li class="' . $cls . '"' . ($n === $current ? ' aria-current="step"' : '') . '><span class="n">' . ($n < $current ? '✓' : $n) . '</span>' . e($label) . '</li>';
    }
    echo '</ol>';
}
