<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'ro';
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['ro', 'en', 'ru'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_SESSION['lang'], time() + (86400 * 30), "/");
}

$lang = $_SESSION['lang'];
$translations = json_decode(file_get_contents(__DIR__ . "/languages/{$lang}.json"), true);

$dsn = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;

if (!$dsn) {
    die("Eroare: Variabila DATABASE_URL nu este setată pe Render.");
}

try {
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    $pdo->exec("SET NAMES 'utf8mb4'");
    
} catch(PDOException $e) {
    error_log("Eroare conexiune BD: " . $e->getMessage());
    die("Eroare: Conexiunea la baza de date nu a fost stabilită.<br>" . htmlspecialchars($e->getMessage()));
}
?>