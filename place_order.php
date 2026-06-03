<?php
require_once 'config.php';
require_once 'language_switcher.php';

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

$catalog = [
    'name_product_1' => ['name' => lang('name_product_1'), 'price' => 800],
    'name_product_2' => ['name' => lang('name_product_2'), 'price' => 990],
    'name_product_3' => ['name' => lang('name_product_3'), 'price' => 320],
    'name_product_4' => ['name' => lang('name_product_4'), 'price' => 650],
    'name_product_5' => ['name' => lang('name_product_5'), 'price' => 650],
    'name_product_6' => ['name' => lang('name_product_6'), 'price' => 630],
    'name_product_7' => ['name' => lang('name_product_7'), 'price' => 700],
    'name_product_8' => ['name' => lang('name_product_8'), 'price' => 900],
    'name_product_9' => ['name' => lang('name_product_9'), 'price' => 1200],
    'name_product_10' => ['name' => lang('name_product_10'), 'price' => 3400],
    'name_product_11' => ['name' => lang('name_product_11'), 'price' => 2500],
    'name_product_12' => ['name' => lang('name_product_12'), 'price' => 2000],
];

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
            VALUES (?, ?, 'pending', ?, NOW())
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
