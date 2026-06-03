<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['orders' => []]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $dateExpression = (($dbDriver ?? '') === 'sqlite')
        ? "strftime('%Y-%m-%d %H:%M', created_at)"
        : "TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI')";

    $stmt = $pdo->prepare("
        SELECT id,
               $dateExpression as order_date,
               total as total_amount,
               status
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
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
