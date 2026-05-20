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

echo "<h2>Debug Environment Variables</h2>";
echo "<pre>";
print_r([
    '$_ENV["DATABASE_URL"]'     => $_ENV['DATABASE_URL'] ?? 'NU EXISTĂ',
    '$_SERVER["DATABASE_URL"]'  => $_SERVER['DATABASE_URL'] ?? 'NU EXISTĂ',
    'getenv("DATABASE_URL")'    => getenv('DATABASE_URL') ?? 'NU EXISTĂ',
    'apache_getenv'             => function_exists('apache_getenv') ? apache_getenv('DATABASE_URL') : 'N/A',
]);
echo "</pre>";

$database_url = $_ENV['DATABASE_URL'] 
             ?? $_SERVER['DATABASE_URL'] 
             ?? getenv('DATABASE_URL')
             ?? apache_getenv('DATABASE_URL') 
             ?? null;

if (empty($database_url)) {
    die("❌ Variabila DATABASE_URL nu este setată pe Render. Verifică Environment Variables!");
}

try {
    $pdo = new PDO($database_url, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec("SET NAMES 'utf8mb4';");
    $pdo->exec("SET search_path TO public;");

    echo "<p style='color:green;'>Conexiune la baza de date reușită!</p>";

} catch (PDOException $e) {
    die("Eroare conexiune BD: " . htmlspecialchars($e->getMessage()));
}
?>