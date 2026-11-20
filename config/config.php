<?php
declare(strict_types=1);

$taskflowDbHost = getenv('TASKFLOW_DB_HOST');
$taskflowDbName = getenv('TASKFLOW_DB_NAME');
$taskflowDbUser = getenv('TASKFLOW_DB_USER');
$taskflowDbPass = getenv('TASKFLOW_DB_PASS');

if ($taskflowDbHost === false || $taskflowDbHost === '') {
    $taskflowDbHost = '127.0.0.1';
}
if ($taskflowDbName === false || $taskflowDbName === '') {
    $taskflowDbName = 'blackola';
}
if ($taskflowDbUser === false || $taskflowDbUser === '') {
    $taskflowDbUser = 'root';
}
if ($taskflowDbPass === false) {
    $taskflowDbPass = '';
}

define('DB_HOST', $taskflowDbHost);
define('DB_NAME', $taskflowDbName);
define('DB_USER', $taskflowDbUser);
define('DB_PASS', $taskflowDbPass);

if (session_status() === PHP_SESSION_NONE) {
    $cookieSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'httponly' => true,
            'secure' => $cookieSecure,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/', '', $cookieSecure, true);
    }
    session_start();
}

function db(): mysqli
{
    static $connection = null;
    if ($connection === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        mysqli_set_charset($connection, 'utf8mb4');
    }
    return $connection;
}

function db_prepare(mysqli $connection, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    if (strlen($types) !== count($params)) {
        throw new InvalidArgumentException('Prepared statement parameter count does not match its types.');
    }

    $statement = mysqli_prepare($connection, $sql);
    if ($types !== '') {
        $bindParams = [$statement, $types];
        foreach ($params as $key => &$value) {
            $bindParams[] = &$value;
        }
        call_user_func_array('mysqli_stmt_bind_param', $bindParams);
    }

    return $statement;
}
