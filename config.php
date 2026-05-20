<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

    $host = 'localhost';
    $port = 5432;
    $dbname = 'maisonlure_db';
    $user = 'postgres';
    $password = 'Catalin';

    try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;client_encoding=utf8",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    
} catch (PDOException $e) {
    echo "❌ Eroare detaliată: " . $e->getMessage() . "<br>";
    echo "Cod eroare: " . $e->getCode();
}

?>