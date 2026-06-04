<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/includes/language_switcher.php';
require_once __DIR__ . '/../app/includes/catalog.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Trebuie sa fii autentificat.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metoda invalida.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['cart']) || !is_array($input['cart']) || empty($input['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cosul este gol.']);
    exit;
}

$catalog = [];
foreach ($products as $product) {
    $catalog[$product['key']] = [
        'name' => lang($product['key']),
        'price' => $product['price'],
    ];
}

$validatedItems = [];
$total = 0;

foreach ($input['cart'] as $item) {
    $key = $item['key'] ?? '';
    $quantity = (int)($item['quantity'] ?? 1);

    if (!isset($catalog[$key]) || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datele cosului sunt invalide.']);
        exit;
    }

    $price = $catalog[$key]['price'];
    $validatedItems[] = [
        'name' => $catalog[$key]['name'],
        'quantity' => $quantity,
        'price' => $price,
    ];
    $total += $price * $quantity;
}

$shippingAddress = $_SESSION['address'] ?? 'Adresa nespecificata';

try {
    $pdo->beginTransaction();

    if (($dbDriver ?? '') === 'sqlite') {
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total, status, shipping_address, created_at)
            VALUES (?, ?, 'pending', ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$_SESSION['user_id'], $total, $shippingAddress]);
        $orderId = $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total, status, shipping_address, created_at)
            VALUES (?, ?, 'pending', ?, CURRENT_TIMESTAMP AT TIME ZONE 'UTC')
            RETURNING id
        ");
        $stmt->execute([$_SESSION['user_id'], $total, $shippingAddress]);
        $orderId = $stmt->fetchColumn();
    }

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, quantity, price, product_name)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($validatedItems as $item) {
        $stmtItem->execute([$orderId, $item['quantity'], $item['price'], $item['name']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Comanda a fost plasata cu succes!']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Order placement error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Eroare interna la plasarea comenzii.']);
}
?>
