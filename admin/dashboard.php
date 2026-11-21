<?php
require __DIR__ . '/_init.php';

$t = db_one(
    "SELECT (SELECT COUNT(*) FROM users) AS users,
            (SELECT COUNT(*) FROM packages) AS packages,
            COUNT(*) AS bookings,
            COALESCE(SUM(status = 'Pending'), 0)   AS pending,
            COALESCE(SUM(status = 'Confirmed'), 0) AS confirmed,
            COALESCE(SUM(status = 'Cancelled'), 0) AS cancelled,
            COALESCE(SUM(status = 'Completed'), 0) AS completed,
            COALESCE(SUM(CASE WHEN status IN ('Confirmed','Completed') THEN total_amount END), 0) AS revenue
       FROM bookings"
);
// last 6 calendar months (including the current one)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $d = new DateTime('first day of this month');
    $d->modify("-$i month");
    $months[$d->format('Y-m')] = ['label' => $d->format('M Y'), 'n' => 0, 'rev' => 0.0];
}
$since = array_key_first($months) . '-01 00:00:00';
foreach (db_all(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS n,
            COALESCE(SUM(CASE WHEN status IN ('Confirmed','Completed') THEN total_amount END), 0) AS rev
       FROM bookings WHERE created_at >= ? GROUP BY ym", 's', [$since]) as $r) {
    if (isset($months[$r['ym']])) { $months[$r['ym']]['n'] = (int) $r['n']; $months[$r['ym']]['rev'] = (float) $r['rev']; }
}
$recent = db_all(
    "SELECT b.id, b.booking_ref, b.contact_name, b.total_amount, b.status, b.created_at, b.cancellation_requested, p.title
       FROM bookings b JOIN packages p ON p.id = b.package_id ORDER BY b.created_at DESC, b.id DESC LIMIT 8"
);
$top = db_all(
    "SELECT p.title, COUNT(b.id) AS n FROM packages p JOIN bookings b ON b.package_id = p.id
      WHERE b.status <> 'Cancelled' GROUP BY p.id ORDER BY n DESC LIMIT 5"
);
$c = admin_counts();

admin_header('Dashboard', 'dashboard', $admin);
?>
<div class="kpis">
  <div class="kpi accent"><div class="l">Revenue</div><div class="n"><?= e(money($t['revenue'])) ?></div><div class="sub">Confirmed + completed bookings</div></div>
  <div class="kpi"><div class="l">Total bookings</div><div class="n"><?= (int) $t['bookings'] ?></div><div class="sub"><?= (int) $t['completed'] ?> completed</div></div>
  <div class="kpi"><div class="l">Customers</div><div class="n"><?= (int) $t['users'] ?></div></div>
  <div class="kpi"><div class="l">Packages</div><div class="n"><?= (int) $t['packages'] ?></div></div>
  <div class="kpi"><div class="l">Pending</div><div class="n"><?= (int) $t['pending'] ?></div><div class="sub">Awaiting confirmation</div></div>
  <div class="kpi"><div class="l">Confirmed</div><div class="n"><?= (int) $t['confirmed'] ?></div></div>
  <div class="kpi"><div class="l">Cancelled</div><div class="n"><?= (int) $t['cancelled'] ?></div></div>
  <div class="kpi"><div class="l">Needs attention</div><div class="n"><?= $c['cancel'] + $c['reviews'] + $c['messages'] ?></div>
    <div class="sub"><?= $c['cancel'] ?> cancel req · <?= $c['reviews'] ?> reviews · <?= $c['messages'] ?> msgs</div></div>
</div>

<div class="quick">
  <a class="btn" href="<?= e(url('admin/add_package.php')) ?>">+ Add package</a>
  <a class="btn btn-outline" href="<?= e(url('admin/manage_bookings.php?status=Pending')) ?>">Review pending bookings</a>
  <a class="btn btn-outline" href="<?= e(url('admin/reviews.php?status=Pending')) ?>">Moderate reviews</a>
  <a class="btn btn-outline" href="<?= e(url('admin/view_messages.php')) ?>">Read messages</a>
</div>

<div class="charts">
  <div class="chart-card"><h2>Bookings per month</h2><div class="chart-box"><canvas id="chart-bookings" role="img" aria-label="Bookings per month"
     data-chart="<?= e(json_encode(['labels' => array_column($months, 'label'), 'values' => array_column($months, 'n')])) ?>"></canvas></div></div>
  <div class="chart-card"><h2>Booking status</h2><div class="chart-box"><canvas id="chart-status" role="img" aria-label="Booking status breakdown"
     data-chart="<?= e(json_encode(['labels' => ['Pending', 'Confirmed', 'Cancelled', 'Completed'], 'values' => [(int) $t['pending'], (int) $t['confirmed'], (int) $t['cancelled'], (int) $t['completed']]])) ?>"></canvas></div></div>
</div>
<div class="charts">
  <div class="chart-card"><h2>Revenue per month</h2><div class="chart-box"><canvas id="chart-revenue" role="img" aria-label="Revenue per month"
     data-chart="<?= e(json_encode(['labels' => array_column($months, 'label'), 'values' => array_column($months, 'rev')])) ?>"></canvas></div></div>
  <div class="chart-card"><h2>Top packages</h2>
    <?php if (!$top): ?><p class="muted">No bookings yet.</p><?php else: ?><ol style="margin:0;padding-left:1.2rem;line-height:2"><?php foreach ($top as $p): ?>
      <li><?= e($p['title']) ?> <span class="muted">· <?= (int) $p['n'] ?> booking<?= (int) $p['n'] === 1 ? '' : 's' ?></span></li><?php endforeach; ?></ol><?php endif; ?></div>
</div>

<h2>Recent bookings</h2>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Reference</th><th>Customer</th><th>Package</th><th>Status</th><th class="num">Total</th><th>Created</th><th></th></tr></thead>
  <tbody><?php if (!$recent): ?><tr><td colspan="7" class="muted center">No bookings yet.</td></tr><?php endif; ?>
  <?php foreach ($recent as $b): ?><tr>
    <td><strong><?= e($b['booking_ref']) ?></strong></td><td><?= e($b['contact_name']) ?></td><td><?= e($b['title']) ?></td>
    <td><?= status_badge($b['status']) ?><?= $b['cancellation_requested'] && in_array($b['status'], ['Pending','Confirmed'], true) ? ' <span class="badge badge-warn">Cancel req.</span>' : '' ?></td>
    <td class="num"><?= e(money($b['total_amount'])) ?></td><td><?= e(fmt_date($b['created_at'])) ?></td>
    <td class="actions"><a class="btn btn-sm btn-outline" href="<?= e(url('admin/booking_view.php?id=' . (int) $b['id'])) ?>">Open</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php admin_footer(['charts' => true]);
