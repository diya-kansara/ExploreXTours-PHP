<?php
require __DIR__ . '/../includes/bootstrap.php';
if (!is_post()) {            // logout is a state change, so it requires a POST with a CSRF token
    redirect('index.php');
}
csrf_verify();
logout_all();
session_start();
flash('info', 'You have been logged out.');
redirect('index.php');
