<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Limba ===
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'ro';
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ro', 'en', 'ru'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_SESSION['lang'], time() + (86400 * 30), "/", "", false, true);
}

$lang = $_SESSION['lang'];
$translations = [];
$langFile = __DIR__ . "/languages/{$lang}.json";
if (file_exists($langFile)) {
    $translations = json_decode(file_get_contents($langFile), true);
}

// $host     = "localhost";
// $port     = "3306";
// $username = "root";
// $password = "";
// $dbname   = "maisonlure";

$host     = "dpg-d83pq1eq1p3s738amib0-a";
$port     = "5432";
$username = "maisonlure_db_user";
$password = "HgknhsihFQZHXXY2INwpSOwdephFHBJP";
$dbname   = "maisonlure_db";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    
    die("Eroare la conectarea la baza de date. Vă rugăm încercați mai târziu.");
}
?>