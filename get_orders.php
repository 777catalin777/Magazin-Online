<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['orders' => []]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id,
               created_at,
               total as total_amount,
               status
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $order['order_date'] = formatLocalDateTime($order['created_at']);
        unset($order['created_at']);

        $stmtItems = $pdo->prepare("
            SELECT product_name, quantity, price as product_price 
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmtItems->execute([$order['id']]);
        $order['items'] = $stmtItems->fetchAll();
    }

    echo json_encode(['orders' => $orders]);
} catch (PDOException $e) {
    error_log("Get orders error: " . $e->getMessage());
    echo json_encode(['orders' => []]);
}
?>
