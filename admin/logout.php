<?php
require __DIR__ . '/../includes/bootstrap.php';
if (!is_post()) { redirect('admin/login.php'); }
csrf_verify();
logout_all();
session_start();
flash('info', 'You have been signed out.');
redirect('admin/login.php');
