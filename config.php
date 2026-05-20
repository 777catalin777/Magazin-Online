<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

$database_url = $_ENV['DATABASE_URL'] 
             ?? $_SERVER['DATABASE_URL'] 
             ?? getenv('DATABASE_URL');

if (empty($database_url)) {
    die("❌ DATABASE_URL nu este setată! Verifică fișierul .env");
}

try {
    $pdo = new PDO($database_url, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec("SET NAMES 'utf8mb4';");
    $pdo->exec("SET search_path TO public;");

} catch (PDOException $e) {
    error_log("EROARE BD: " . $e->getMessage());
    die("Eroare la conectarea la baza de date:<br>" . htmlspecialchars($e->getMessage()));
}

echo "<p style='color:green; background:#d4edda; padding:10px;'>✅ Conexiune BD reușită!</p>";

date_default_timezone_set('Europe/Bucharest');
?>