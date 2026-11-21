<?php
/**
 * ExploreX Tours - application bootstrap.
 * Loads configuration, hardens PHP/session settings, connects helpers.
 * Every page starts with:  require __DIR__ . '/includes/bootstrap.php';
 */
declare(strict_types=1);

if (defined('EXPLOREX_BOOT')) {
    return;
}
define('EXPLOREX_BOOT', true);
define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ExploreX Tours is not configured yet.\n"
       . "Copy config/config.sample.php to config/config.php and set your database details.\n";
    exit;
}
$GLOBALS['CONFIG'] = require $configFile;

function config(string $key, $default = null)
{
    return $GLOBALS['CONFIG'][$key] ?? $default;
}

date_default_timezone_set((string) config('timezone', 'Asia/Kolkata'));
mb_internal_encoding('UTF-8');

// ---- error handling: never leak internals in production ------------------
$isDev = config('environment', 'production') === 'development';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

set_exception_handler(function (Throwable $e) use ($isDev) {
    error_log('[ExploreX] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $detail = $isDev ? '<pre style="white-space:pre-wrap;background:#fff;border:1px solid #ddd;padding:12px;margin-top:16px">'
        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>' : '';
    echo '<!doctype html><meta charset="utf-8"><title>Something went wrong</title>'
       . '<body style="font-family:system-ui,sans-serif;background:#faf6ee;color:#22302a;max-width:560px;margin:12vh auto;padding:0 20px">'
       . '<h1 style="font-family:Georgia,serif">Something went wrong</h1>'
       . '<p>We hit an unexpected problem. Please try again in a moment.</p>' . $detail
       . '<p><a href="javascript:history.back()">Go back</a></p></body>';
    exit;
});

// ---- security headers ------------------------------------------------------
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; "
         . "script-src 'self' https://cdn.jsdelivr.net; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
}

// ---- session ---------------------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() === PHP_SESSION_NONE) {
    session_name('EXPLOREXSESSID');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
// idle timeout: 2 hours, absolute lifetime: 12 hours
$now = time();
if (isset($_SESSION['started_at']) && ($now - (int) $_SESSION['started_at'] > 43200
    || $now - (int) ($_SESSION['last_seen'] ?? $now) > 7200)) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['started_at'] = $_SESSION['started_at'] ?? $now;
$_SESSION['last_seen']  = $now;

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/booking_lib.php';
