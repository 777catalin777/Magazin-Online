<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/includes/language_switcher.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_page.php");
    exit();
}

$statusOptions = [
    'pending' => 'În așteptare',
    'processing' => 'În procesare',
    'shipped' => 'Expediată',
    'delivered' => 'Livrată',
];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $token = $_POST['csrf_token'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (!isValidCsrfToken($token)) {
        $error = 'Cerere invalidă. Reincarcă pagina și încearcă din nou.';
    } elseif ($orderId <= 0 || !array_key_exists($status, $statusOptions)) {
        $error = 'Date invalide pentru actualizarea comenzii.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            if ($stmt->rowCount() === 0) {
                $error = 'Comanda nu a fost gasita.';
            } else {
                $message = 'Statusul comenzii ' . $orderId . ' a fost actualizată.';
            }
        } catch (PDOException $e) {
            error_log("Admin order status update error: " . $e->getMessage());
            $error = 'Nu s-a putut actualiza statusul comenzii.';
        }
    }
}

function money($value)
{
    return number_format((float)$value, 0, '.', ' ') . ' MDL';
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

try {
    $orderStats = $pdo->query("
        SELECT COUNT(*) AS orders,
               COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
               COALESCE(SUM(total), 0) AS revenue
        FROM orders
    ")->fetch();

    $stats = [
        'users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'orders' => (int)$orderStats['orders'],
        'pending' => (int)$orderStats['pending'],
        'revenue' => (float)$orderStats['revenue'],
    ];

    $ordersStmt = $pdo->query("
        SELECT orders.id,
               orders.total,
               orders.status,
               orders.created_at,
               users.name AS customer_name,
               users.email AS customer_email,
               users.phone AS customer_phone,
               users.address AS customer_address
        FROM orders
        JOIN users ON users.id = orders.user_id
        ORDER BY orders.created_at DESC
        LIMIT 20
    ");
    $orders = $ordersStmt->fetchAll();

    $itemsByOrder = [];
    if ($orders) {
        $orderIds = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemsStmt = $pdo->prepare("
            SELECT order_id, product_name, quantity, price
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
    }
    unset($order);

    $usersStmt = $pdo->query("
        SELECT id, name, email, role, phone, address
        FROM users
        ORDER BY id DESC
        LIMIT 10
    ");
    $users = $usersStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Admin dashboard load error: " . $e->getMessage());
    $stats = ['users' => 0, 'orders' => 0, 'pending' => 0, 'revenue' => 0];
    $orders = [];
    $users = [];
    $error = $error ?: 'Nu s-au putut incarca datele din panoul de administrare.';
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(lang('site_title')) ?> | Admin</title>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="assets/css/user_page.css">
    <style>
        body {
            align-items: stretch;
            justify-content: flex-start;
        }

        .admin-dashboard {
            max-width: 1500px;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: #ffffff;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 16px 28px -14px rgba(0, 0, 0, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .metric-card i {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            background: #eef2ff;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
        }

        .metric-label {
            color: #64748b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .metric-value {
            color: #111827;
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 0.25rem;
        }

        .admin-content {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
            gap: 1.5rem;
        }

        .admin-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 0.85rem;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: top;
        }

        .admin-table th {
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-form select {
            border: 1px solid #cbd5e1;
            border-radius: 0.6rem;
            padding: 0.55rem 0.75rem;
            background: white;
            font: inherit;
            color: #1e293b;
        }

        .icon-btn {
            width: 2.35rem;
            height: 2.35rem;
            border: none;
            border-radius: 0.6rem;
            background: #111827;
            color: white;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .muted {
            color: #64748b;
            font-size: 0.9rem;
        }

        @media (max-width: 1100px) {
            .admin-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }

            .admin-table,
            .admin-table tbody,
            .admin-table tr,
            .admin-table td {
                display: block;
                width: 100%;
            }

            .admin-table thead {
                display: none;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/css/admin_page.css">
</head>

<body>
    <div class="dashboard-container admin-dashboard">
        <div class="dashboard-header">
            <div class="logo">
                <span>Admin Maison Lure</span>
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn-outline-light"><i class='bx bx-store'></i> Magazin</a>
                <a href="logout.php" class="btn-outline-light"><i class='bx bx-log-out'></i> <?= e(lang('logout')) ?></a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert-custom alert-success">
                <i class='bx bx-check-circle'></i> <?= e($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-custom alert-error">
                <i class='bx bx-error-circle'></i> <?= e($error) ?>
            </div>
        <?php endif; ?>

        <section class="admin-grid">
            <div class="metric-card">
                <i class='bx bx-user'></i>
                <div class="metric-label">Utilizatori</div>
                <div class="metric-value"><?= e($stats['users']) ?></div>
            </div>
            <div class="metric-card">
                <i class='bx bx-package'></i>
                <div class="metric-label">Comenzi</div>
                <div class="metric-value"><?= e($stats['orders']) ?></div>
            </div>
            <div class="metric-card">
                <i class='bx bx-time-five'></i>
                <div class="metric-label">In asteptare</div>
                <div class="metric-value"><?= e($stats['pending']) ?></div>
            </div>
            <div class="metric-card">
                <i class='bx bx-wallet'></i>
                <div class="metric-label">Venit total</div>
                <div class="metric-value"><?= e(money($stats['revenue'])) ?></div>
            </div>
        </section>

        <main class="admin-content">
            <section class="card">
                <div class="card-title">
                    <i class='bx bx-receipt'></i>
                    <span>Ultimele comenzi</span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="orders-empty">
                        <i class='bx bx-package'></i>
                        <h3>Nu există comenzi</h3>
                        <p>Comenzile clientilor vor apărea aici.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-list">
                        <?php foreach ($orders as $order): ?>
                            <?php $status = $order['status'] ?? 'pending'; ?>
                            <article class="order-card">
                                <div class="order-header">
                                    <div class="order-id-date">
                                        <strong>Comanda #<?= e($order['id']) ?></strong>
                                        <span class="order-date"><?= e($order['order_date']) ?></span>
                                    </div>
                                    <span class="order-status status-<?= e($status) ?>">
                                        <?= e($statusOptions[$status] ?? $status) ?>
                                    </span>
                                </div>

                                <p><strong><?= e($order['customer_name']) ?></strong> <span class="muted"><?= e($order['customer_email']) ?></span></p>
                                <p class="muted">Telefon: <?= e($order['customer_phone'] ?: 'Nespecificat') ?></p>
                                <p class="muted">Livrare: <?= e($order['customer_address'] ?: 'Nespecificată') ?></p>

                                <ul class="product-list">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li class="product-item">
                                            <span class="product-name"><?= e($item['product_name']) ?></span>
                                            <span class="product-qty">x <?= e($item['quantity']) ?></span>
                                            <span class="product-price"><?= e(money($item['price'])) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="order-footer">
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="order_id" value="<?= e($order['id']) ?>">
                                        <select name="status" aria-label="Status comandă">
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                                <option value="<?= e($value) ?>" <?= $value === $status ? 'selected' : '' ?>>
                                                    <?= e($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="icon-btn" type="submit" name="update_order_status" title="Salvează statusul">
                                            <i class='bx bx-save'></i>
                                        </button>
                                    </form>
                                    <span class="total-amount"><?= e(money($order['total'])) ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="card">
                <div class="card-title">
                    <i class='bx bx-group'></i>
                    <span>Clienti recenti</span>
                </div>

                <?php if (empty($users)): ?>
                    <div class="orders-empty">
                        <i class='bx bx-user-x'></i>
                        <h3>Nu exista utilizatori</h3>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nume</th>
                                <th>Contact si adresa</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($user['name']) ?></strong>
                                        <div class="muted"><?= e($user['email']) ?></div>
                                    </td>
                                    <td>
                                        <div><strong>Telefon:</strong> <?= e($user['phone'] ?: 'Nespecificat') ?></div>
                                        <div class="muted"><strong>Adresa:</strong> <?= e($user['address'] ?: 'Nespecificată') ?></div>
                                    </td>
                                    <td>
                                        <span class="role-badge"><?= e($user['role'] ?: 'user') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </aside>
        </main>
    </div>
</body>

</html>
