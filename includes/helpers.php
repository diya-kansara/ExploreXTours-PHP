<?php
declare(strict_types=1);

// ---- output & URLs ---------------------------------------------------------
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = (string) config('base_path', '');
    if ($configured !== '') {
        return $base = '/' . trim($configured, '/');
    }
    $root = str_replace('\\', '/', (string) realpath(APP_ROOT));
    $doc  = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($doc !== '' && stripos($root, $doc) === 0) {
        return $base = rtrim(substr($root, strlen($doc)), '/');
    }
    return $base = '';
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#i', $path) ? $path : url($path)));
    exit;
}

/** Only allows base-relative internal paths such as "book.php?package_id=3". */
function safe_next(?string $next, string $fallback = 'user/dashboard.php'): string
{
    $next = trim((string) $next);
    if ($next !== '' && preg_match('#^[A-Za-z0-9_\-][A-Za-z0-9_\-/\.]*(\?[A-Za-z0-9_\-=&%\.\[\]]*)?$#', $next) && !str_contains($next, '..')) {
        return $next;
    }
    return $fallback;
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return ltrim(substr($uri, strlen(base_path())), '/');
}

// ---- flash messages --------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---- CSRF ------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Must be called by every state-changing request. */
function csrf_verify(): void
{
    $sent = (string) ($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if ($sent === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        abort(419, 'Your session expired or the request could not be verified. Please go back, refresh the page and try again.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Require POST + valid CSRF token (for action endpoints). */
function require_post(): void
{
    if (!is_post()) {
        header('Allow: POST');
        abort(405, 'This action must be submitted from the website.');
    }
    csrf_verify();
}

// ---- input -----------------------------------------------------------------
function post_str(string $key, int $max = 255): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

function get_str(string $key, int $max = 120): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

function get_int(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? null;
    return (is_string($v) && preg_match('/^-?\d{1,9}$/', $v)) ? (int) $v : $default;
}

function post_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? null;
    return (is_string($v) && preg_match('/^-?\d{1,9}$/', $v)) ? (int) $v : $default;
}

function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function valid_phone(string $p): bool
{
    return (bool) preg_match('/^\+?[0-9][0-9\s\-]{7,17}$/', $p);
}

/** Password policy shared by registration, profile and admin forms. */
function password_problems(string $pw): array
{
    $errors = [];
    if (strlen($pw) < 8)                  $errors[] = 'Password must be at least 8 characters.';
    if (strlen($pw) > 72)                 $errors[] = 'Password must be at most 72 characters.';
    if (!preg_match('/[A-Za-z]/', $pw))   $errors[] = 'Password must contain a letter.';
    if (!preg_match('/[0-9]/', $pw))      $errors[] = 'Password must contain a number.';
    return $errors;
}

// ---- formatting ------------------------------------------------------------
function money($n): string
{
    return '₹' . number_format((float) $n, 0, '.', ',');
}

function fmt_date(?string $d, string $format = 'd M Y'): string
{
    if (!$d) {
        return '—';
    }
    $t = strtotime($d);
    return $t ? date($format, $t) : '—';
}

function status_badge(string $status): string
{
    $cls = [
        'Pending' => 'warn', 'Confirmed' => 'ok', 'Completed' => 'info', 'Cancelled' => 'bad',
        'Success' => 'ok', 'Failed' => 'bad', 'Approved' => 'ok', 'Rejected' => 'bad',
    ][$status] ?? 'muted';
    return '<span class="badge badge-' . $cls . '">' . e($status) . '</span>';
}

function stars(float $rating, bool $withNumber = false): string
{
    $full = (int) round($rating);
    $html = '<span class="stars" role="img" aria-label="' . e(number_format($rating, 1)) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="' . ($i <= $full ? 'on' : 'off') . '" aria-hidden="true">★</span>';
    }
    $html .= '</span>';
    if ($withNumber) {
        $html .= ' <span class="muted small">' . e(number_format($rating, 1)) . '</span>';
    }
    return $html;
}

function pkg_image_url(?string $file): string
{
    $file = basename((string) $file);
    if ($file !== '') {
        if (is_file(APP_ROOT . '/uploads/packages/' . $file)) {
            return url('uploads/packages/' . rawurlencode($file));
        }
        if (is_file(APP_ROOT . '/images/' . $file)) {
            return url('images/' . rawurlencode($file));
        }
    }
    return url('assets/img/placeholder.svg');
}

function pluralize(int $n, string $one, ?string $many = null): string
{
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}

// ---- random references -----------------------------------------------------
function random_code(int $len, string $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'): string
{
    $out = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < $len; $i++) {
        $out .= $alphabet[random_int(0, $max)];
    }
    return $out;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}
