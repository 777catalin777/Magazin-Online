<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii autentificat.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cart = $input['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Coșul este gol.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$address = $_SESSION['address'] ?? 'Adresă nesetată';
$total = 0;

foreach ($cart as $item) {
    $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
}

try {
    $pdo->beginTransaction();

    $order_number = 'ORD-' . strtoupper(uniqid());

    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, shipping_address, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $order_number, $total, $address]);
    $order_id = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_key, product_name, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($cart as $item) {
        $product_key = $item['key'];
        $product_name = $item['name'] ?? 'Produs';
        $quantity = $item['quantity'] ?? 1;
        $price = $item['price'] ?? 0;
        $stmtItem->execute([$order_id, $product_key, $product_name, $quantity, $price]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A apărut o eroare. Încearcă din nou.']);
}
?>