<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/includes/language_switcher.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Cerere invalidă. Reîncarcă pagina și încearcă din nou.');
    }

    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = "Email-ul și parola sunt obligatorii!";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, password, role, phone, address FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['phone'] = $user['phone'] ?? '';
                    $_SESSION['address'] = $user['address'] ?? '';

                    if ($user['role'] === 'admin') {
                        header("Location: admin_page.php");
                    } else {
                        header("Location: user_page.php");
                    }
                    exit();
                } else {
                    $_SESSION['login_error'] = "Email-ul sau parola sunt incorecte!";
                }
            } catch (PDOException $e) {
                $_SESSION['login_error'] = "Eroare la autentificare.";
                error_log("Login error: " . $e->getMessage());
            }
        }
        $_SESSION['active_form'] = 'login';
        header("Location: login.php");
        exit();
    }

    if (isset($_POST['register'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['register_error'] = "Toate câmpurile sunt obligatorii!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['register_error'] = "Adresa de email nu este validă!";
        } elseif (strlen($password) < 6) {
            $_SESSION['register_error'] = "Parola trebuie să aibă minim 6 caractere!";
        } elseif ($password !== $confirm) {
            $_SESSION['register_error'] = "Parolele nu se potrivesc!";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);

                if ($stmt->fetch()) {
                    $_SESSION['register_error'] = "Există deja un cont cu acest email!";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                    $stmt->execute([$name, $email, $hashed_password]);

                    $_SESSION['register_success'] = "Cont creat cu succes! Te poți autentifica.";
                }
            } catch (PDOException $e) {
                $_SESSION['register_error'] = "Eroare la înregistrare.";
                error_log("Register error: " . $e->getMessage());
            }
        }
        $_SESSION['active_form'] = isset($_SESSION['register_success']) ? 'login' : 'register';
        header("Location: login.php");
        exit();
    }

    if (isset($_POST['update_profile'])) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            $_SESSION['profile_error'] = "Numele este obligatoriu!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $_SESSION['user_id']]);
                $_SESSION['name'] = $name;
                $_SESSION['phone'] = $phone;
                $_SESSION['address'] = $address;
                $_SESSION['profile_success'] = "Profil actualizat cu succes!";
            } catch (PDOException $e) {
                $_SESSION['profile_error'] = "Eroare la actualizarea profilului.";
                error_log("Profile update error: " . $e->getMessage());
            }
        }
        header("Location: user_page.php");
        exit();
    }

    if (isset($_POST['change_password'])) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }

        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password'])) {
                if (strlen($new_password) < 6) {
                    $_SESSION['profile_error'] = "Parola nouă trebuie să aibă cel puțin 6 caractere!";
                } elseif ($new_password !== $confirm_password) {
                    $_SESSION['profile_error'] = "Parolele nu se potrivesc!";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    $_SESSION['profile_success'] = "Parola a fost schimbată cu succes!";
                }
            } else {
                $_SESSION['profile_error'] = "Parola curentă este incorectă!";
            }
        } catch (PDOException $e) {
            $_SESSION['profile_error'] = "Eroare la schimbarea parolei.";
            error_log("Password change error: " . $e->getMessage());
        }
        header("Location: user_page.php");
        exit();
    }
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_page.php");
    exit();
}

$success = $_SESSION['profile_success'] ?? '';
$error = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(lang('site_title')) ?> | Contul meu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap">
    <link rel="preload" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"></noscript>
    <link rel="stylesheet" href="assets/css/user_page.css">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/images/favicon/site.webmanifest">
</head>

<body>
    <div id="order-notifications" class="notification-stack" aria-live="polite" aria-atomic="true"></div>

    <div class="dashboard-shell">
        <header class="dashboard-header">
            <a href="index.php" class="header-brand" aria-label="Maison Lure">
                <span class="brand-copy">
                    <strong>Maison Lure</strong>
                </span>
            </a>
            <div class="header-actions">
                <a href="index.php" class="btn-outline-light"><i class='bx bx-shopping-bag'></i> Magazin</a>
                <a href="logout.php" class="btn-outline-light"><i class='bx bx-log-out'></i> Deconectare</a>
            </div>
        </header>

        <main class="dashboard-main">
            <section class="dashboard-page-head">
                <div>
                    <span class="section-kicker">Panou client</span>
                    <h1>Contul meu</h1>
                </div>
                <span class="page-status-pill"><i class='bx bx-check-shield'></i> Activ</span>
            </section>

            <?php if ($success): ?>
                <div class="alert-custom alert-success">
                    <i class='bx bx-check-circle'></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-custom alert-error">
                    <i class='bx bx-error-circle'></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">

                <div class="card profile-card">
                    <div class="card-title">
                        <i class='bx bx-user-circle'></i>
                        <span>Profil</span>
                    </div>
                    <div class="profile-avatar">
                        <div class="avatar-circle">
                            <i class='bx bx-user'></i>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nume</div>
                        <div class="info-value"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Telefon</div>
                        <div class="info-value"><?= htmlspecialchars($_SESSION['phone'] ?? 'Nesetat') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Adresă</div>
                        <div class="info-value"><?= htmlspecialchars($_SESSION['address'] ?? 'Nesetată') ?></div>
                    </div>
                </div>

                <div class="card account-card">
                    <div class="card-title">
                        <i class='bx bx-edit-alt'></i>
                        <span>Actualizează datele</span>
                    </div>
                    <form method="POST" class="card-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="input-box">
                            <i class='bx bx-user'></i>
                            <input type="text" name="name" placeholder="Nume complet"
                                value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                        </div>
                        <div class="input-box">
                            <i class='bx bx-phone'></i>
                            <input type="tel" name="phone" placeholder="Număr de telefon"
                                value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
                        </div>
                        <div class="input-box">
                            <i class='bx bx-map'></i>
                            <input type="text" name="address" placeholder="Adresă de livrare"
                                value="<?= htmlspecialchars($_SESSION['address'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn-primary"><i class='bx bx-save'></i> Salvează modificările</button>
                    </form>
                </div>

                <div class="card cart-card" id="current-cart-card">
                    <div class="cart-card-head">
                        <div class="cart-title-group">
                            <span class="cart-title-icon"><i class='bx bx-cart'></i></span>
                            <div>
                                <h2>Coș</h2>
                                <span>Rezumat comandă</span>
                            </div>
                        </div>
                        <div class="cart-count-pill">
                            <strong id="cart-total-qty">0</strong>
                            <span id="cart-total-label">produse</span>
                        </div>
                    </div>
                    <div id="cart-items-list" class="cart-items-list">
                        <div class="cart-empty-placeholder">
                            <i class='bx bx-cart-alt'></i>
                            <h3>Coșul este gol</h3>
                            <p>Nicio selecție momentan.</p>
                            <a href="index.php" class="cart-shop-link">
                                <i class='bx bx-shopping-bag'></i>
                                <span>Magazin</span>
                            </a>
                        </div>
                    </div>
                    <div class="cart-summary">
                        <span>Total comandă</span>
                        <strong><span id="cart-total-price">0</span> MDL</strong>
                    </div>
                    <div class="cart-actions">
                        <button id="clear-cart-btn" class="cart-action cart-action-secondary" type="button">
                            <i class='bx bx-trash'></i>
                            <span>Golește coșul</span>
                        </button>
                        <button id="place-order-btn" class="cart-action cart-action-primary" type="button">
                            <i class='bx bx-check-circle'></i>
                            <span>Plasează comanda</span>
                        </button>
                    </div>
                </div>

                <div class="card orders-card">
                    <div class="card-title">
                        <i class='bx bx-purchase-tag'></i>
                        <span>Comenzi</span>
                    </div>
                    <div id="orders-list">
                        <div class="orders-empty">
                            <i class='bx bx-package'></i>
                            <h3>Se încarcă...</h3>
                        </div>
                    </div>
                </div>
            </div>

            <section class="card full-width security-card">
                <div class="card-title">
                    <i class='bx bx-lock-alt'></i>
                    <span>Schimbă parola</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <input type="hidden" name="change_password" value="1">
                    <div class="input-box">
                        <i class='bx bx-key'></i>
                        <input type="password" name="current_password" placeholder="Parola curentă" required>
                    </div>
                    <div class="input-box">
                        <i class='bx bx-lock'></i>
                        <input type="password" name="new_password" placeholder="Parola nouă" required>
                    </div>
                    <div class="input-box">
                        <i class='bx bx-check-shield'></i>
                        <input type="password" name="confirm_password" placeholder="Confirmă parola nouă" required>
                    </div>
                    <button type="submit" class="btn-primary"><i class='bx bx-refresh'></i> Resetează parola</button>
                </form>
            </section>
        </main>

        <footer class="dashboard-footer">
            <span>Maison Lure</span>
            <span>Cont client</span>
        </footer>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let cart = [];
            const MAX_CART_QUANTITY = 99;

            function normalizeQuantity(value) {
                const quantity = Number(value);
                return Number.isInteger(quantity) && quantity > 0
                    ? Math.min(quantity, MAX_CART_QUANTITY)
                    : 1;
            }

            try {
                const storedCart = JSON.parse(localStorage.getItem("cart"));
                cart = Array.isArray(storedCart)
                    ? storedCart
                        .filter(item => item && typeof item === "object" && !Array.isArray(item) && typeof item.key === "string" && item.key !== "")
                        .map(item => ({ ...item, quantity: normalizeQuantity(item.quantity) }))
                    : [];
            } catch {
                localStorage.removeItem("cart");
            }

            function displayCart() {
                const cartCard = document.getElementById("current-cart-card");
                const container = document.getElementById("cart-items-list");
                const totalQtySpan = document.getElementById("cart-total-qty");
                const totalLabelSpan = document.getElementById("cart-total-label");
                const totalPriceSpan = document.getElementById("cart-total-price");
                const clearCartButton = document.getElementById("clear-cart-btn");

                if (!container) return;

                if (cart.length === 0) {
                    container.innerHTML = `<div class="cart-empty-placeholder">
                                        <i class='bx bx-cart-alt'></i>
                                        <h3>Coșul este gol</h3>
                                        <p>Nicio selecție momentan.</p>
                                        <a href="index.php" class="cart-shop-link">
                                            <i class='bx bx-shopping-bag'></i>
                                            <span>Magazin</span>
                                        </a>
                                    </div>`;
                    cartCard?.classList.add("is-empty");
                    if (clearCartButton) clearCartButton.disabled = true;
                    if (totalQtySpan) totalQtySpan.textContent = "0";
                    if (totalLabelSpan) totalLabelSpan.textContent = "produse";
                    if (totalPriceSpan) totalPriceSpan.textContent = "0";
                    return;
                }

                cartCard?.classList.remove("is-empty");
                if (clearCartButton) clearCartButton.disabled = false;

                let totalQty = 0;
                let totalPrice = 0;
                let html = '<ul class="cart-item-list">';

                cart.forEach((item, index) => {
                    const parsedQty = Number(item.quantity);
                    const parsedPrice = Number(item.price);
                    const qty = normalizeQuantity(parsedQty);
                    const price = Number.isFinite(parsedPrice) && parsedPrice >= 0 ? parsedPrice : 0;
                    const itemTotal = qty * price;
                    totalQty += qty;
                    totalPrice += itemTotal;
                    const productName = item.name || "Produs";
                    const safeProductName = escapeHtml(productName);
                    const productImage = escapeHtml(item.image || "");
                    const media = productImage
                        ? `<img src="${productImage}" alt="${safeProductName}">`
                        : `<i class='bx bx-package'></i>`;

                    html += `
                <li class="cart-item">
                    <div class="cart-item-media">${media}</div>
                    <div class="cart-item-body">
                        <div class="cart-item-top">
                            <strong class="cart-item-name">${safeProductName}</strong>
                            <strong class="cart-item-total">${itemTotal.toFixed(0)} MDL</strong>
                        </div>
                        <div class="cart-item-bottom">
                            <span class="cart-item-meta">${price.toFixed(0)} MDL / buc.</span>
                            <div class="cart-quantity-controls">
                                <button class="cart-qty-btn cart-decrease-qty" data-index="${index}" type="button" aria-label="Scade cantitatea pentru ${safeProductName}">
                                    <i class='bx bx-minus'></i>
                                </button>
                                <span class="cart-quantity-value">${qty}</span>
                                <button class="cart-qty-btn cart-increase-qty" data-index="${index}" type="button" aria-label="Crește cantitatea pentru ${safeProductName}">
                                    <i class='bx bx-plus'></i>
                                </button>
                                <button class="cart-remove-item" data-index="${index}" type="button" aria-label="Elimină ${safeProductName}">
                                    <i class='bx bx-x'></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            `;
                });

                html += '</ul>';
                container.innerHTML = html;
                if (totalQtySpan) totalQtySpan.textContent = totalQty;
                if (totalLabelSpan) totalLabelSpan.textContent = totalQty === 1 ? "produs" : "produse";
                if (totalPriceSpan) totalPriceSpan.textContent = totalPrice.toFixed(0);
            }

            function saveAndRefresh() {
                localStorage.setItem("cart", JSON.stringify(cart));
                displayCart();
            }

            function showOrderNotification(message, type = "info") {
                const notifications = document.getElementById("order-notifications");
                if (!notifications) return;

                const variants = {
                    success: {
                        icon: "bx-check-circle",
                        title: "Comandă plasată"
                    },
                    error: {
                        icon: "bx-error-circle",
                        title: "A apărut o problemă"
                    },
                    warning: {
                        icon: "bx-shopping-bag",
                        title: "Coș gol"
                    },
                    info: {
                        icon: "bx-info-circle",
                        title: "Notificare"
                    }
                };
                const variant = variants[type] || variants.info;
                const notification = document.createElement("div");
                notification.className = `order-notification order-notification-${type}`;
                notification.setAttribute("role", type === "error" ? "alert" : "status");

                const icon = document.createElement("i");
                icon.className = `bx ${variant.icon}`;
                icon.setAttribute("aria-hidden", "true");

                const content = document.createElement("div");
                content.className = "order-notification-content";

                const title = document.createElement("strong");
                title.textContent = variant.title;

                const text = document.createElement("span");
                text.textContent = message;

                const closeButton = document.createElement("button");
                closeButton.type = "button";
                closeButton.className = "order-notification-close";
                closeButton.setAttribute("aria-label", "Închide notificarea");
                closeButton.innerHTML = "<i class='bx bx-x'></i>";

                content.append(title, text);
                notification.append(icon, content, closeButton);
                notifications.appendChild(notification);

                const closeNotification = () => {
                    notification.classList.add("is-hiding");
                    setTimeout(() => notification.remove(), 220);
                };

                closeButton.addEventListener("click", closeNotification);
                setTimeout(closeNotification, 4200);
            }

            document.getElementById("cart-items-list")?.addEventListener("click", function (e) {
                const target = e.target.closest?.("button[data-index]");
                if (!target) return;

                const indexAttr = target.getAttribute("data-index");
                const idx = parseInt(indexAttr, 10);
                if (isNaN(idx) || !cart[idx]) return;

                if (target.classList.contains("cart-decrease-qty")) {
                    const quantity = normalizeQuantity(cart[idx].quantity);
                    if (quantity > 1) {
                        cart[idx].quantity = quantity - 1;
                    } else {
                        cart.splice(idx, 1);
                    }
                    saveAndRefresh();
                } else if (target.classList.contains("cart-increase-qty")) {
                    cart[idx].quantity = Math.min(normalizeQuantity(cart[idx].quantity) + 1, MAX_CART_QUANTITY);
                    saveAndRefresh();
                } else if (target.classList.contains("cart-remove-item")) {
                    cart.splice(idx, 1);
                    saveAndRefresh();
                }
            });

            document.getElementById("clear-cart-btn")?.addEventListener("click", function () {
                cart = [];
                saveAndRefresh();
            });

            document.getElementById("place-order-btn")?.addEventListener("click", function () {
                if (cart.length === 0) {
                    showOrderNotification("Adaugă produse înainte de a plasa o comandă.", "warning");
                    return;
                }

                fetch("api/place_order.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": <?= json_encode(csrfToken()) ?>
                    },
                    body: JSON.stringify({ cart: cart })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showOrderNotification(data.message || "Comanda a fost trimisă cu succes.", "success");
                            cart = [];
                            localStorage.setItem("cart", JSON.stringify(cart));
                            displayCart();
                            loadOrders();
                        } else {
                            showOrderNotification(data.message || "Comanda nu a putut fi plasată.", "error");
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        showOrderNotification("A apărut o eroare la plasarea comenzii.", "error");
                    });
            });

            function escapeHtml(str) {
                return String(str).replace(/[&<>"']/g, function (m) {
                    if (m === '&') return '&amp;';
                    if (m === '<') return '&lt;';
                    if (m === '>') return '&gt;';
                    if (m === '"') return '&quot;';
                    if (m === "'") return '&#039;';
                    return m;
                });
            }

            function loadOrders() {
                fetch("api/get_orders.php")
                    .then(response => response.json())
                    .then(data => {
                        const ordersContainer = document.getElementById("orders-list");
                        if (!ordersContainer) return;

                        if (data.orders && data.orders.length > 0) {
                            let html = `<div class="order-list">`;
                            data.orders.forEach(order => {

                                let statusClass = '';
                                let statusText = '';
                                switch (order.status) {
                                    case 'pending':
                                        statusClass = 'status-pending';
                                        statusText = 'În așteptare';
                                        break;
                                    case 'processing':
                                        statusClass = 'status-processing';
                                        statusText = 'În procesare';
                                        break;
                                    case 'shipped':
                                        statusClass = 'status-shipped';
                                        statusText = 'Expediată';
                                        break;
                                    case 'delivered':
                                        statusClass = 'status-delivered';
                                        statusText = 'Livrată';
                                        break;
                                    default:
                                        statusClass = 'status-pending';
                                        statusText = order.status || 'În așteptare';
                                }

                                html += `
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-id-date">
                                                <strong>Comanda ${order.id}</strong>
                                                <span class="order-date">${order.order_date}</span>
                                            </div>
                                            <div class="order-status ${statusClass}">${statusText}</div>
                                        </div>
                                        <div class="order-products">
                                            <ul class="product-list">
                                `;
                                order.items.forEach(item => {
                                    html += `
                                        <li class="product-item">
                                            <span class="product-name">${escapeHtml(item.product_name)}</span>
                                            <span class="product-qty">× ${item.quantity}</span>
                                            <span class="product-price">${item.product_price} MDL</span>
                                        </li>
                                    `;
                                });
                                html += `
                                            </ul>
                                        </div>
                                        <div class="order-footer">
                                            <span class="total-label">Total:</span>
                                            <span class="total-amount">${order.total_amount} MDL</span>
                                        </div>
                                    </div>
                                `;
                            });
                            html += `</div>`;
                            ordersContainer.innerHTML = html;
                        } else {
                            ordersContainer.innerHTML = `
                                <div class="orders-empty">
                                    <i class='bx bx-package'></i>
                                    <h3>Nu ai nicio comandă încă</h3>
                                    <p>Descoperă colecția noastră și plasează prima ta comandă.</p>
                                    <a href="index.php" class="btn-link"><i class='bx bx-cart'></i> Explorează magazinul</a>
                                </div>
                            `;
                        }
                    })
                    .catch(err => {
                        console.error("Eroare la încărcarea comenzilor:", err);
                        const ordersContainer = document.getElementById("orders-list");
                        if (ordersContainer) {
                            ordersContainer.innerHTML = `<div class="orders-empty"><i class='bx bx-error-circle'></i><h3>Eroare la încărcarea comenzilor</h3></div>`;
                        }
                    });
            }

            displayCart();
            loadOrders();
        });
    </script>
</body>

</html>
