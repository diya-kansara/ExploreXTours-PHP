<?php
/**
 * Create or reset an administrator from the command line (the secure setup path).
 *   php scripts/create_admin.php "Full Name" admin@example.com
 *   php scripts/create_admin.php --reset admin@example.com
 * The password is read from the terminal prompt (or ADMIN_PASSWORD env var) - never from the web.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
require __DIR__ . '/../includes/bootstrap.php';

$args = array_slice($argv, 1);
$reset = ($args[0] ?? '') === '--reset';
if ($reset) { array_shift($args); }
if (($reset && count($args) !== 1) || (!$reset && count($args) !== 2)) {
    exit("Usage:\n  php scripts/create_admin.php \"Full Name\" email@example.com\n  php scripts/create_admin.php --reset email@example.com\n");
}
$email = strtolower($reset ? $args[0] : $args[1]);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { exit("Invalid email address.\n"); }

$pw = getenv('ADMIN_PASSWORD') ?: '';
if ($pw === '') {
    fwrite(STDOUT, 'Password (min 8 chars, letter + number): ');
    if (stripos(PHP_OS, 'WIN') !== 0) { shell_exec('stty -echo'); }
    $pw = trim((string) fgets(STDIN));
    if (stripos(PHP_OS, 'WIN') !== 0) { shell_exec('stty echo'); }
    fwrite(STDOUT, "\n");
}
if ($problems = password_problems($pw)) { exit(implode("\n", $problems) . "\n"); }
$hash = password_hash($pw, PASSWORD_DEFAULT);

if ($reset) {
    $n = db_exec('UPDATE admins SET password = ? WHERE email = ?', 'ss', [$hash, $email]);
    exit($n ? "Password updated for $email.\n" : "No administrator with that email (or the password is unchanged).\n");
}
$first = (int) db_value('SELECT COUNT(*) FROM admins') === 0;
try {
    db_insert('INSERT INTO admins (name, email, password, role) VALUES (?,?,?,?)', 'ssss', [$args[0], $email, $hash, $first ? 'super_admin' : 'admin']);
    echo "Administrator created: $email (" . ($first ? 'super_admin' : 'admin') . ")\n";
} catch (mysqli_sql_exception $e) {
    echo $e->getCode() === 1062 ? "An administrator with that email already exists. Use --reset to change the password.\n" : "Error: " . $e->getMessage() . "\n";
}
