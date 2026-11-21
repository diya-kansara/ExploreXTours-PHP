<?php
require __DIR__ . '/../includes/bootstrap.php';
require_post();
$user = require_user();

$ref = post_str('ref', 24);
$b = user_booking_by_ref($ref, (int) $user['id']);       // ownership check
if (!$b) {
    abort(404, 'We could not find that booking in your account.');
}
if (!can_request_cancellation($b)) {
    flash('error', 'This booking can no longer be cancelled online. Please contact us.');
    redirect('user/booking_view.php?ref=' . rawurlencode($ref));
}
db_exec(
    'UPDATE bookings SET cancellation_requested = 1, cancellation_reason = ?, cancellation_requested_at = NOW()
      WHERE id = ? AND user_id = ? AND cancellation_requested = 0',
    'sii', [post_str('reason', 500), (int) $b['id'], (int) $user['id']]
);
flash('success', 'Cancellation request sent. We\'ll review it and update your booking.');
redirect('user/booking_view.php?ref=' . rawurlencode($ref));
