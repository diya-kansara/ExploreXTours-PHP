<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_user();
$uid = (int) $user['id'];

$stats = db_one(
    "SELECT COUNT(*) AS total,
            SUM(status IN ('Pending','Confirmed') AND travel_date >= CURDATE()) AS upcoming,
            SUM(status = 'Completed') AS completed,
            COALESCE(SUM(CASE WHEN status IN ('Confirmed','Completed') THEN total_amount END), 0) AS spent
       FROM bookings WHERE user_id = ?", 'i', [$uid]
);
$upcoming = db_all(
    "SELECT b.id, b.booking_ref, b.travel_date, b.return_date, b.guests, b.status, b.total_amount, b.cancellation_requested,
            p.title, p.location, p.image
       FROM bookings b JOIN packages p ON p.id = b.package_id
      WHERE b.user_id = ? AND b.status IN ('Pending','Confirmed') AND b.travel_date >= CURDATE()
      ORDER BY b.travel_date LIMIT 3", 'i', [$uid]
);
$toReview = db_all(
    "SELECT b.id, b.booking_ref, p.title FROM bookings b JOIN packages p ON p.id = b.package_id
      WHERE b.user_id = ? AND b.status = 'Completed' AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.id)
      ORDER BY b.return_date DESC LIMIT 3", 'i', [$uid]
);
$recent = db_all(
    "SELECT b.booking_ref, b.created_at, b.status, b.total_amount, p.title
       FROM bookings b JOIN packages p ON p.id = b.package_id WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 5", 'i', [$uid]
);

dash_open('overview', 'My dashboard', $user);
?>
<h1>Hello, <?= e(explode(' ', $user['name'])[0]) ?></h1>
<p class="muted">Here's a snapshot of your trips with ExploreX.</p>
<div class="stat-grid">
  <div class="stat"><div class="n"><?= (int) $stats['total'] ?></div><div class="l">Total bookings</div></div>
  <div class="stat"><div class="n"><?= (int) $stats['upcoming'] ?></div><div class="l">Upcoming trips</div></div>
  <div class="stat"><div class="n"><?= (int) $stats['completed'] ?></div><div class="l">Completed trips</div></div>
  <div class="stat"><div class="n"><?= e(money($stats['spent'])) ?></div><div class="l">Booked value</div></div>
</div>

<?php if ($toReview): ?>
  <div class="alert alert-info" style="margin-bottom:24px"><strong>How was your trip?</strong>
    <?php foreach ($toReview as $r): ?> <a href="<?= e(url('user/review.php?ref=' . rawurlencode($r['booking_ref']))) ?>">Review “<?= e($r['title']) ?>”</a>.<?php endforeach; ?></div>
<?php endif; ?>

<h2>Upcoming trips</h2>
<?php if (!$upcoming): ?>
  <div class="empty"><div class="big">🧳</div><h3>No upcoming trips</h3><p class="muted">When you book a tour it will show up here.</p>
    <a class="btn" href="<?= e(url('packages.php')) ?>">Browse tours</a></div>
<?php else: foreach ($upcoming as $t): ?>
  <div class="trip">
    <img src="<?= e(pkg_image_url($t['image'])) ?>" alt="" loading="lazy">
    <div><h3><?= e($t['title']) ?></h3>
      <div class="meta">📍 <?= e($t['location']) ?> · <?= e(fmt_date($t['travel_date'])) ?> → <?= e(fmt_date($t['return_date'])) ?> · <?= e(pluralize((int) $t['guests'], 'guest')) ?></div>
      <div class="meta">Ref <strong><?= e($t['booking_ref']) ?></strong></div></div>
    <div class="side"><?= status_badge($t['status']) ?><?php if ($t['cancellation_requested']): ?><span class="badge badge-warn">Cancellation requested</span><?php endif; ?>
      <a class="btn btn-sm btn-outline" href="<?= e(url('user/booking_view.php?ref=' . rawurlencode($t['booking_ref']))) ?>">View details</a></div>
  </div>
<?php endforeach; endif; ?>

<?php if ($recent): ?>
<h2 style="margin-top:36px">Recent activity</h2>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Reference</th><th>Tour</th><th>Booked on</th><th>Status</th><th class="num">Total</th></tr></thead>
  <tbody><?php foreach ($recent as $b): ?>
    <tr><td><a href="<?= e(url('user/booking_view.php?ref=' . rawurlencode($b['booking_ref']))) ?>"><?= e($b['booking_ref']) ?></a></td>
      <td><?= e($b['title']) ?></td><td><?= e(fmt_date($b['created_at'])) ?></td><td><?= status_badge($b['status']) ?></td>
      <td class="num"><?= e(money($b['total_amount'])) ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php endif;
dash_close();
