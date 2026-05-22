<?php
require_once 'config.php';
require_once 'language_switcher.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Trebuie să fii autentificat.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodă invalidă.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['cart']) || !is_array($input['cart']) || empty($input['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Coșul este gol.']);
    exit;
}

$cart = $input['cart'];
$userId = $_SESSION['user_id'];
$total = 0;

foreach ($cart as $item) {
    $price = floatval($item['price'] ?? 0);
    $qty = intval($item['quantity'] ?? 1);
    $total += $price * $qty;
}

$shippingAddress = $_SESSION['address'] ?? 'Adresă nespecificată';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, total, status, shipping_address, created_at) 
        VALUES (?, ?, 'pending', ?, NOW())
    ");
    $stmt->execute([$userId, $total, $shippingAddress]);
    $orderId = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($cart as $item) {
        $productId = 0;
        $productName = $item['name'] ?? 'Produs';
        $quantity = intval($item['quantity'] ?? 1);
        $price = floatval($item['price'] ?? 0);

        $stmtItem->execute([$orderId, $productId, $productName, $quantity, $price]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Comanda a fost plasată cu succes!']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Order placement error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare internă: ' . $e->getMessage()]);
}
?>