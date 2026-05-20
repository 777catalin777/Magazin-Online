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

$dsn = "postgres://maisonlure_db_user:HgknhsihFQZHXXY2INwpSOwdephFHBJP@dpg-d83pq1eq1p3s738amib0-a/maisonlure_db";

try {
    $pdo = new PDO("pgsql:" . substr($dsn, 11), null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $pdo->exec("SET NAMES 'utf8'");
    
} catch(PDOException $e) {
    error_log("Eroare conexiune BD: " . $e->getMessage());
    $pdo = null;
    die("Eroare: Conexiunea la baza de date nu a fost stabilită. <br>" . htmlspecialchars($e->getMessage()));
}
?>