<?php
require __DIR__ . '/../includes/bootstrap.php';
require_post();
require_admin();

$id = post_int('id');
$p = db_one('SELECT id, title, image FROM packages WHERE id = ?', 'i', [$id]);
if (!$p) { abort(404, 'Package not found.'); }
$action = post_str('action', 10);

if ($action === 'toggle') {
    db_exec('UPDATE packages SET is_active = 1 - is_active WHERE id = ?', 'i', [$id]);
    flash('success', '“' . $p['title'] . '” visibility updated.');
} elseif ($action === 'delete') {
    $n = (int) db_value('SELECT COUNT(*) FROM bookings WHERE package_id = ?', 'i', [$id]);
    if ($n > 0) {
        // bookings.package_id is ON DELETE RESTRICT: keep booking history intact
        flash('error', '“' . $p['title'] . '” has ' . $n . ' booking(s) and cannot be deleted. Hide it instead so customers no longer see it.');
    } else {
        $gallery = db_all('SELECT image FROM package_images WHERE package_id = ?', 'i', [$id]);
        db_exec('DELETE FROM packages WHERE id = ?', 'i', [$id]);       // itinerary/hotels/inclusions/gallery/wishlist/reviews cascade
        delete_package_image($p['image']);
        foreach ($gallery as $g) { delete_package_image($g['image']); }
        flash('success', '“' . $p['title'] . '” was deleted.');
    }
} else {
    abort(400, 'Unknown action.');
}
redirect('admin/manage_packages.php');
