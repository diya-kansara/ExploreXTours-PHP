<?php
require __DIR__ . '/_init.php';
$status = get_str('status', 10); if (!in_array($status, ['Success', 'Failed'], true)) { $status = ''; }
$page = max(1, get_int('page', 1)); $per = 20;
$w = $status !== '' ? 'WHERE y.status = ?' : ''; $types = $status !== '' ? 's' : ''; $params = $status !== '' ? [$status] : [];
$sum = db_one("SELECT COUNT(*) AS n, COALESCE(SUM(status='Success'),0) AS ok, COALESCE(SUM(status='Failed'),0) AS bad,
                      COALESCE(SUM(CASE WHEN status='Success' THEN amount END),0) AS amount FROM payments");
$total = (int) db_value("SELECT COUNT(*) FROM payments y $w", $types, $params);
$pages = max(1, (int) ceil($total / $per)); $page = min($page, $pages);
$rows = db_all("SELECT y.*, b.booking_ref, b.id AS bid, b.contact_name FROM payments y JOIN bookings b ON b.id = y.booking_id $w
                ORDER BY y.created_at DESC, y.id DESC LIMIT ? OFFSET ?", $types . 'ii', array_merge($params, [$per, ($page - 1) * $per]));
admin_header('Payments (simulated)', 'payments', $admin);
?>
<div class="demo-banner"><span>ℹ️</span><span>All records here are <strong>simulated demonstration payments</strong>. No card data is collected and no money moves.</span></div>
<div class="kpis"><div class="kpi"><div class="l">Attempts</div><div class="n"><?= (int) $sum['n'] ?></div></div>
  <div class="kpi"><div class="l">Successful</div><div class="n"><?= (int) $sum['ok'] ?></div></div>
  <div class="kpi"><div class="l">Failed</div><div class="n"><?= (int) $sum['bad'] ?></div></div>
  <div class="kpi"><div class="l">Demo value</div><div class="n"><?= e(money($sum['amount'])) ?></div></div></div>
<form class="toolbar" method="get"><div class="f"><label for="s">Status</label><select id="s" name="status" onchange="this.form.submit()">
  <option value="">All</option><option <?= $status === 'Success' ? 'selected' : '' ?>>Success</option><option <?= $status === 'Failed' ? 'selected' : '' ?>>Failed</option></select></div>
  <noscript><button class="btn btn-sm">Filter</button></noscript></form>
<div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Transaction</th><th>Booking</th><th>Method</th><th>Status</th><th class="num">Amount</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="6" class="center muted" style="padding:32px">No payments yet.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?><tr><td><?= e(fmt_date($r['created_at'], 'd M Y, H:i')) ?></td><td><code><?= e($r['transaction_ref']) ?></code></td>
  <td><a href="<?= e(url('admin/booking_view.php?id=' . (int) $r['bid'])) ?>"><?= e($r['booking_ref']) ?></a><div class="muted small"><?= e($r['contact_name']) ?></div></td>
  <td><?= e($r['method']) ?></td><td><?= status_badge($r['status']) ?></td><td class="num"><?= e(money($r['amount'])) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?= pager($page, $pages, 'admin/payments.php', ['status' => $status]) ?>
<?php admin_footer();
