<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

$availableLanguages = ['ro', 'en', 'ru'];
$appTimezone = new DateTimeZone('Europe/Chisinau');
$storageTimezone = new DateTimeZone('UTC');

function formatLocalDateTime($value, $format = 'Y-m-d H:i')
{
    global $appTimezone, $storageTimezone;

    if (empty($value)) {
        return '';
    }

    try {
        return (new DateTimeImmutable((string)$value, $storageTimezone))
            ->setTimezone($appTimezone)
            ->format($format);
    } catch (Exception $e) {
        error_log("Date formatting error: " . $e->getMessage());
        return (string)$value;
    }
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidCsrfToken($token)
{
    return is_string($token)
        && $token !== ''
        && hash_equals(csrfToken(), $token);
}

if (isset($_GET['lang']) && in_array($_GET['lang'], $availableLanguages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_SESSION['lang'], [
        'expires' => time() + (86400 * 30),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $availableLanguages, true)
        ? $_COOKIE['lang']
        : 'ro';
}

$lang = $_SESSION['lang'];
$translations = [];
$projectRoot = dirname(__DIR__, 2);
$langFile = $projectRoot . "/languages/{$lang}.json";
if (file_exists($langFile)) {
    $translations = json_decode(file_get_contents($langFile), true) ?: [];
}

$usePostgres = getenv('PGHOST') && getenv('PGDATABASE') && getenv('PGUSER');
$dbDriver = $usePostgres ? 'pgsql' : 'sqlite';

try {
    if ($dbDriver === 'pgsql') {
        $host = getenv('PGHOST');
        $port = getenv('PGPORT') ?: '5432';
        $dbname = getenv('PGDATABASE');
        $username = getenv('PGUSER');
        $password = getenv('PGPASSWORD') ?: '';
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    } else {
        $username = null;
        $password = null;
        $dsn = "sqlite:" . $projectRoot . "/database/database.sqlite";
    }

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    if ($dbDriver === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
    }
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Eroare la conectarea la baza de date. Va rugam incercati mai tarziu.");
}
?>
