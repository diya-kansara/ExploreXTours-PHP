<?php
require __DIR__ . '/_init.php';

$q = get_str('q', 80); $status = get_str('status', 12); $from = get_str('from', 10); $to = get_str('to', 10);
$cancelOnly = get_str('cancel', 1) === '1';
$page = max(1, get_int('page', 1)); $per = 15;
if ($status !== '' && !in_array($status, BOOKING_STATUSES, true)) { $status = ''; }
if ($from !== '' && !valid_date($from)) { $from = ''; }
if ($to !== '' && !valid_date($to)) { $to = ''; }

$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(b.booking_ref LIKE ? OR b.contact_name LIKE ? OR b.contact_email LIKE ? OR p.title LIKE ? OR p.location LIKE ?)';
    $types .= 'sssss'; array_push($params, $like, $like, $like, $like, $like);
}
if ($status !== '')  { $where[] = 'b.status = ?'; $types .= 's'; $params[] = $status; }
if ($from !== '')    { $where[] = 'b.travel_date >= ?'; $types .= 's'; $params[] = $from; }
if ($to !== '')      { $where[] = 'b.travel_date <= ?'; $types .= 's'; $params[] = $to; }
if ($cancelOnly)     { $where[] = "b.cancellation_requested = 1 AND b.status IN ('Pending','Confirmed')"; }
$w = implode(' AND ', $where);

$total = (int) db_value("SELECT COUNT(*) FROM bookings b JOIN packages p ON p.id = b.package_id WHERE $w", $types, $params);
$pages = max(1, (int) ceil($total / $per)); $page = min($page, $pages);
$rows = db_all(
    "SELECT b.id, b.booking_ref, b.contact_name, b.contact_email, b.travel_date, b.guests, b.status, b.total_amount, b.created_at,
            b.cancellation_requested, p.title,
            (SELECT COUNT(*) FROM payments y WHERE y.booking_id = b.id AND y.status = 'Success') AS paid
       FROM bookings b JOIN packages p ON p.id = b.package_id WHERE $w
      ORDER BY b.cancellation_requested DESC, b.created_at DESC, b.id DESC LIMIT ? OFFSET ?",
    $types . 'ii', array_merge($params, [$per, ($page - 1) * $per])
);

admin_header('Bookings', 'bookings', $admin);
?>
<form class="toolbar" method="get" action="">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Ref, name, email, tour"></div>
  <div class="f"><label for="st">Status</label><select id="st" name="status"><option value="">All</option>
    <?php foreach (BOOKING_STATUSES as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="from">Travel from</label><input id="from" type="date" name="from" value="<?= e($from) ?>"></div>
  <div class="f"><label for="to">Travel to</label><input id="to" type="date" name="to" value="<?= e($to) ?>"></div>
  <label class="check" style="padding-bottom:10px"><input type="checkbox" name="cancel" value="1" <?= $cancelOnly ? 'checked' : '' ?>> Cancellation requests</label>
  <button class="btn btn-sm" type="submit">Filter</button>
  <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/manage_bookings.php')) ?>">Clear</a>
</form>
<p class="muted small"><?= (int) $total ?> booking<?= $total === 1 ? '' : 's' ?></p>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Reference</th><th>Customer</th><th>Tour</th><th>Travel</th><th>Status</th><th>Paid</th><th class="num">Total</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="8" class="center muted" style="padding:32px">No bookings match.</td></tr><?php endif; ?>
  <?php foreach ($rows as $b): ?><tr>
    <td><a href="<?= e(url('admin/booking_view.php?id=' . (int) $b['id'])) ?>"><strong><?= e($b['booking_ref']) ?></strong></a><div class="muted small"><?= e(fmt_date($b['created_at'])) ?></div></td>
    <td><?= e($b['contact_name']) ?><div class="muted small"><?= e($b['contact_email']) ?></div></td>
    <td class="wrap"><?= e($b['title']) ?><div class="muted small"><?= e(pluralize((int) $b['guests'], 'guest')) ?></div></td>
    <td><?= e(fmt_date($b['travel_date'])) ?></td>
    <td><?= status_badge($b['status']) ?><?= $b['cancellation_requested'] && in_array($b['status'], ['Pending','Confirmed'], true) ? '<div><span class="badge badge-warn">Cancel req.</span></div>' : '' ?></td>
    <td><?= (int) $b['paid'] ? '<span class="badge badge-ok">Paid</span>' : '<span class="badge badge-muted">No</span>' ?></td>
    <td class="num"><?= e(money($b['total_amount'])) ?></td>
    <td class="actions"><div class="row-actions">
      <?php if ($b['status'] === 'Pending') echo admin_action_form(url('admin/booking_action.php'), ['id' => $b['id'], 'action' => 'status', 'to' => 'Confirmed', 'back' => 'list'], 'Confirm', 'btn btn-sm'); ?>
      <?php if (in_array($b['status'], ['Pending', 'Confirmed'], true)) echo admin_action_form(url('admin/booking_action.php'), ['id' => $b['id'], 'action' => 'status', 'to' => 'Cancelled', 'back' => 'list'], 'Cancel', 'btn btn-sm btn-outline', 'Cancel booking ' . $b['booking_ref'] . '?'); ?>
      <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/booking_view.php?id=' . (int) $b['id'])) ?>">Details</a></div></td>
  </tr><?php endforeach; ?></tbody></table></div>
<?= pager($page, $pages, 'admin/manage_bookings.php', ['q' => $q, 'status' => $status, 'from' => $from, 'to' => $to, 'cancel' => $cancelOnly ? '1' : '']) ?>
<?php admin_footer();
