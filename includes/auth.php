<?php
/**
 * Authentication & authorisation (customers and administrators).
 * Customers  -> $_SESSION['user_id']
 * Admins     -> $_SESSION['admin_id']   (separate table, separate guard)
 * Logging in as one role always logs the other role out.
 */
declare(strict_types=1);

const LOGIN_WINDOW_MINUTES = 15;
const LOGIN_MAX_PER_EMAIL  = 5;
const LOGIN_MAX_PER_IP     = 20;

// ---- customers -------------------------------------------------------------
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return $cache = null;
    }
    $u = db_one('SELECT id, name, email, phone, created_at FROM users WHERE id = ?', 'i', [(int) $id]);
    if (!$u) {            // account deleted while logged in
        unset($_SESSION['user_id']);
        return $cache = null;
    }
    return $cache = $u;
}

function require_user(): array
{
    $u = current_user();
    if (!$u) {
        $next = current_path();
        redirect('user/login.php?next=' . rawurlencode(safe_next($next, '')));
    }
    return $u;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    unset($_SESSION['admin_id']);
    $_SESSION['user_id'] = $userId;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// ---- administrators --------------------------------------------------------
function current_admin(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['admin_id'] ?? null;
    if (!$id) {
        return $cache = null;
    }
    $a = db_one('SELECT id, name, email, role, last_login_at, created_at FROM admins WHERE id = ?', 'i', [(int) $id]);
    if (!$a) {
        unset($_SESSION['admin_id']);
        return $cache = null;
    }
    return $cache = $a;
}

function require_admin(): array
{
    $a = current_admin();
    if (!$a) {
        redirect('admin/login.php');
    }
    return $a;
}

function require_super_admin(): array
{
    $a = require_admin();
    if ($a['role'] !== 'super_admin') {
        abort(403, 'Only a super administrator can do this.');
    }
    return $a;
}

function login_admin(int $adminId): void
{
    session_regenerate_id(true);
    unset($_SESSION['user_id']);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db_exec('UPDATE admins SET last_login_at = NOW() WHERE id = ?', 'i', [$adminId]);
}

function logout_all(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---- brute-force throttle --------------------------------------------------
function login_blocked(string $scope, string $email): bool
{
    $since = date('Y-m-d H:i:s', time() - LOGIN_WINDOW_MINUTES * 60);
    $byEmail = (int) db_value('SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND email = ? AND attempted_at > ?', 'sss', [$scope, strtolower($email), $since]);
    $byIp    = (int) db_value('SELECT COUNT(*) FROM login_attempts WHERE scope = ? AND ip = ? AND attempted_at > ?', 'sss', [$scope, client_ip(), $since]);
    return $byEmail >= LOGIN_MAX_PER_EMAIL || $byIp >= LOGIN_MAX_PER_IP;
}

function login_failed(string $scope, string $email): void
{
    db_exec('INSERT INTO login_attempts (scope, email, ip) VALUES (?, ?, ?)', 'sss', [$scope, strtolower(mb_substr($email, 0, 150)), client_ip()]);
    // opportunistic cleanup
    db_exec('DELETE FROM login_attempts WHERE attempted_at < ?', 's', [date('Y-m-d H:i:s', time() - 86400)]);
}

function login_succeeded(string $scope, string $email): void
{
    db_exec('DELETE FROM login_attempts WHERE scope = ? AND email = ?', 'ss', [$scope, strtolower($email)]);
}

/**
 * Shared credential check. Returns [row|null, error|null].
 * The error text is identical for "unknown e-mail" and "wrong password".
 */
function check_credentials(string $scope, string $email, string $password): array
{
    if (login_blocked($scope, $email)) {
        return [null, 'Too many failed attempts. Please wait ' . LOGIN_WINDOW_MINUTES . ' minutes and try again.'];
    }
    $table = $scope === 'admin' ? 'admins' : 'users';
    $row = db_one("SELECT id, name, password FROM {$table} WHERE email = ?", 's', [$email]);
    // always run a hash comparison so response time does not reveal valid e-mails
    $hash = $row['password'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
    $ok = password_verify($password, $hash);
    if (!$row || !$ok) {
        login_failed($scope, $email);
        return [null, 'Incorrect email or password.'];
    }
    login_succeeded($scope, $email);
    if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
        db_exec("UPDATE {$table} SET password = ? WHERE id = ?", 'si', [password_hash($password, PASSWORD_DEFAULT), (int) $row['id']]);
    }
    return [$row, null];
}
