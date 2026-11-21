<?php
/**
 * Database access - MySQLi with prepared statements ONLY.
 * No query in the application concatenates user input into SQL.
 */
declare(strict_types=1);

function db(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(
                (string) config('db_host', '127.0.0.1'),
                (string) config('db_user', 'root'),
                (string) config('db_pass', ''),
                (string) config('db_name', 'tours_travels'),
                (int) config('db_port', 3306)
            );
            $conn->set_charset('utf8mb4');
            $conn->query("SET time_zone = '" . date('P') . "'");
        } catch (mysqli_sql_exception $e) {
            error_log('[ExploreX] DB connection failed: ' . $e->getMessage());
            throw new RuntimeException('Could not connect to the database. Check config/config.php and that MySQL is running.');
        }
    }
    return $conn;
}

/** Prepare + bind + execute. $types uses mysqli letters (s,i,d). */
function db_run(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function db_all(string $sql, string $types = '', array $params = []): array
{
    $stmt = db_run($sql, $types, $params);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function db_one(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

function db_value(string $sql, string $types = '', array $params = [])
{
    $row = db_one($sql, $types, $params);
    return $row ? reset($row) : null;
}

/** Executes INSERT/UPDATE/DELETE, returns affected rows. */
function db_exec(string $sql, string $types = '', array $params = []): int
{
    $stmt = db_run($sql, $types, $params);
    $n = $stmt->affected_rows;
    $stmt->close();
    return (int) $n;
}

function db_insert(string $sql, string $types = '', array $params = []): int
{
    $stmt = db_run($sql, $types, $params);
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

function db_transaction(callable $fn)
{
    $c = db();
    $c->begin_transaction();
    try {
        $result = $fn();
        $c->commit();
        return $result;
    } catch (Throwable $e) {
        $c->rollback();
        throw $e;
    }
}
