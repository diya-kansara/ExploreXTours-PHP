<?php
/**
 * ExploreX Tours - local configuration.
 *
 * 1. Copy this file to  config/config.php
 * 2. Adjust the values for your machine.
 * config/config.php is listed in .gitignore and must never be committed.
 */
return [
    // --- Database (WAMP defaults: user "root", empty password) ---
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'tours_travels',
    'db_user' => 'root',
    'db_pass' => '',

    // --- Application ---
    // '' = detect automatically (works for http://localhost/ExploreXTours-PHP/ and for a virtual host).
    // Set e.g. '/ExploreXTours-PHP' only if detection fails.
    'base_path' => '',

    // 'development' shows PHP/DB error details. Use 'production' anywhere public.
    'environment' => 'development',
    'timezone'    => 'Asia/Kolkata',

    // --- Uploads ---
    'upload_max_bytes' => 3 * 1024 * 1024,   // 3 MB per image

    // --- Booking rules ---
    'max_guests_per_booking' => 12,
    'min_days_before_travel' => 1,           // earliest travel date = today + N days
];
