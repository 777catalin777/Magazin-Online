<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$availableLanguages = ['ro', 'en', 'ru'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $availableLanguages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_SESSION['lang'], time() + (86400 * 30), "/", "", false, true);
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $availableLanguages, true)
        ? $_COOKIE['lang']
        : 'ro';
}

$lang = $_SESSION['lang'];
$translations = [];
$langFile = __DIR__ . "/languages/{$lang}.json";
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
        $dsn = "sqlite:" . __DIR__ . "/database.sqlite";
    }

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    if ($dbDriver === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Eroare la conectarea la baza de date. Va rugam incercati mai tarziu.");
}
?>
