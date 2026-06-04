<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/includes/language_switcher.php';
require_once __DIR__ . '/../app/includes/catalog.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond($payload, $status = 200)
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(['success' => false, 'message' => 'Trebuie sa fii autentificat.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(['success' => false, 'message' => 'Metoda invalida.'], 405);
}

if (!isValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    respond(['success' => false, 'message' => 'Cerere invalida.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['cart']) || !is_array($input['cart']) || empty($input['cart'])) {
    respond(['success' => false, 'message' => 'Cosul este gol.'], 422);
}

if (count($input['cart']) > 50) {
    respond(['success' => false, 'message' => 'Cosul contine prea multe produse.'], 422);
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
$seenKeys = [];

foreach ($input['cart'] as $item) {
    if (!is_array($item)) {
        respond(['success' => false, 'message' => 'Datele cosului sunt invalide.'], 422);
    }

    $key = $item['key'] ?? '';
    $rawQuantity = $item['quantity'] ?? 1;
    $quantity = filter_var($rawQuantity, FILTER_VALIDATE_INT);

    if (!is_string($key)
        || $quantity === false
        || !isset($catalog[$key])
        || $quantity <= 0
        || $quantity > 99
        || isset($seenKeys[$key])
    ) {
        respond(['success' => false, 'message' => 'Datele cosului sunt invalide.'], 422);
    }
    $seenKeys[$key] = true;

    $price = $catalog[$key]['price'];
    $validatedItems[] = [
        'name' => $catalog[$key]['name'],
        'quantity' => $quantity,
        'price' => $price,
    ];
    $total += $price * $quantity;
}

try {
    $addressStmt = $pdo->prepare("SELECT address FROM users WHERE id = ?");
    $addressStmt->execute([$_SESSION['user_id']]);
    $shippingAddress = $addressStmt->fetchColumn();
    if ($shippingAddress === false) {
        respond(['success' => false, 'message' => 'Trebuie sa fii autentificat.'], 401);
    }

    $shippingAddress = trim((string)$shippingAddress);
    if ($shippingAddress === '') {
        $shippingAddress = 'Adresa nespecificata';
    }

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
    respond(['success' => true, 'message' => 'Comanda a fost plasata cu succes!'], 201);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Order placement error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'Eroare interna la plasarea comenzii.'], 500);
}
