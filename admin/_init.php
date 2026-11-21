<?php
/** Shared guard for every admin page: bootstrap + layout + authenticated admin. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_layout.php';
$admin = require_admin();
