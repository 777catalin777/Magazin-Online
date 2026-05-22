<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$order_id = $_GET['order_id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    exit;
}

$stmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($items);
?>