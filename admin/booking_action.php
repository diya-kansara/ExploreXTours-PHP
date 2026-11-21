<?php
/** POST-only endpoint for state-changing booking operations (CSRF protected, admin only). */
require __DIR__ . '/../includes/bootstrap.php';
require_post();
require_admin();

$id = post_int('id');
$b = db_one('SELECT id, booking_ref, status, return_date, cancellation_requested FROM bookings WHERE id = ?', 'i', [$id]);
if (!$b) { abort(404, 'Booking not found.'); }
$back = post_str('back', 10) === 'list' ? 'admin/manage_bookings.php' : 'admin/booking_view.php?id=' . $id;
$action = post_str('action', 20);

if ($action === 'status') {
    $to = post_str('to', 12);
    if (!in_array($to, allowed_status_changes($b['status']), true)) {
        flash('error', 'A ' . strtolower($b['status']) . ' booking cannot be changed to ' . strtolower($to) . '.');
    } elseif ($to === 'Completed' && $b['return_date'] > date('Y-m-d')) {
        flash('error', 'A booking can only be marked completed after the trip has ended (' . fmt_date($b['return_date']) . ').');
    } else {
        // the WHERE status = ? makes the transition atomic: a stale page cannot overwrite a newer status
        $n = db_exec('UPDATE bookings SET status = ?, cancellation_requested = IF(? = \'Cancelled\', 0, cancellation_requested) WHERE id = ? AND status = ?',
            'ssis', [$to, $to, $id, $b['status']]);
        flash($n ? 'success' : 'error', $n ? 'Booking ' . $b['booking_ref'] . ' is now ' . $to . '.' : 'The booking was changed by someone else. Please review it again.');
    }
} elseif ($action === 'reject_cancel') {
    db_exec('UPDATE bookings SET cancellation_requested = 0 WHERE id = ?', 'i', [$id]);
    flash('success', 'Cancellation request declined. The booking stays active.');
} else {
    abort(400, 'Unknown action.');
}
redirect($back);
