<?php
require __DIR__ . '/_init.php';
require __DIR__ . '/_package_form.php';
$id = get_int('id');
if ($id < 1) { abort(404, 'Package not found.'); }
handle_package_form($id);
