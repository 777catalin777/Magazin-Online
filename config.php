<?php
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

$servername = "dpg-d83pq1eq1p3s738amib0-a";
$port = "5432";
$username = "maisonlure_db_user";
$password = "HgknhsihFQZHXXY2INwpSOwdephFHBJP";
$dbname = "maisonlure_db";


try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES 'utf8'");

} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    $conn = null;
    echo "<!-- Eroare BD: " . htmlspecialchars($e->getMessage()) . " -->";
}

?>