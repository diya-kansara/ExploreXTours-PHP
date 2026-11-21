<?php
/** POST endpoint: add/remove a package from the logged-in customer's wishlist. */
require __DIR__ . '/includes/bootstrap.php';
require_post();

$wantsJson = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
$back = safe_next($_POST['back'] ?? '', 'user/wishlist.php');
$user = current_user();

if (!$user) {
    if ($wantsJson) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'login_required']);
        exit;
    }
    flash('info', 'Please log in to save tours.');
    redirect('user/login.php?next=' . rawurlencode($back));
}

$pid = post_int('package_id');
$exists = $pid > 0 && db_value('SELECT COUNT(*) FROM packages WHERE id = ? AND is_active = 1', 'i', [$pid]) > 0;
if (!$exists) {
    abort(404, 'That package does not exist.');
}

$uid = (int) $user['id'];
$saved = db_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND package_id = ?', 'ii', [$uid, $pid]) > 0;
if ($saved) {
    db_exec('DELETE FROM wishlist WHERE user_id = ? AND package_id = ?', 'ii', [$uid, $pid]);
} else {
    // composite primary key + IGNORE => duplicate entries are impossible even on double-click races
    db_exec('INSERT IGNORE INTO wishlist (user_id, package_id) VALUES (?, ?)', 'ii', [$uid, $pid]);
}
$nowSaved = !$saved;
$count = (int) db_value('SELECT COUNT(*) FROM wishlist WHERE user_id = ?', 'i', [$uid]);

if ($wantsJson) {
    header('Content-Type: application/json');
    echo json_encode(['saved' => $nowSaved, 'count' => $count]);
    exit;
}
flash('success', $nowSaved ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
redirect($back);
