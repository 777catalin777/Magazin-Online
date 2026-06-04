<?php
require_once __DIR__ . '/../app/config/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['orders' => []]);
    exit;
}

try {
    $ordersStmt = $pdo->prepare("
        SELECT id, created_at, total AS total_amount, status
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $ordersStmt->execute([$_SESSION['user_id']]);
    $orders = $ordersStmt->fetchAll();

    $itemsByOrder = [];
    if ($orders) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemsStmt = $pdo->prepare("
            SELECT order_id, product_name, quantity, price AS product_price
            FROM order_items
            WHERE order_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $itemsStmt->execute($orderIds);
        foreach ($itemsStmt->fetchAll() as $item) {
            $itemsByOrder[$item['order_id']][] = $item;
        }
    }

    foreach ($orders as &$order) {
        $order['order_date'] = formatLocalDateTime($order['created_at']);
        $order['items'] = $itemsByOrder[$order['id']] ?? [];
        unset($order['created_at']);
    }
    unset($order);

    echo json_encode(['orders' => $orders]);
} catch (PDOException $e) {
    error_log("Get orders error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['orders' => []]);
}
