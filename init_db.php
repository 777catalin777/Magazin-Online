<?php
require_once 'config.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $pdo->exec($sql);
    echo "Tabelul 'users' a fost creat (sau există deja).\n";
} catch (PDOException $e) {
    echo "Eroare: " . $e->getMessage() . "\n";
}
?>