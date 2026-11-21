<?php
require __DIR__ . '/includes/bootstrap.php';
require_post();

$back  = safe_next($_POST['back'] ?? '', 'index.php');
$email = post_str('email', 150);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Please enter a valid email address to subscribe.');
} else {
    db_exec('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)', 's', [strtolower($email)]);
    flash('success', 'Thanks for subscribing. You\'re on the list.');
}
redirect($back);
