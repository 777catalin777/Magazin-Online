<?php
require_once 'config.php';
require_once 'language_switcher.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = "Email și parola sunt obligatorii!";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, password, role, phone, address FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
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
                    $_SESSION['login_error'] = "Email sau parolă incorectă!";
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

                if ($stmt->rowCount() > 0) {
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
        $_SESSION['active_form'] = 'register';
        header("Location: login.php");
        exit();
    }

    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            $_SESSION['profile_error'] = "Numele este obligatoriu!";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE email = ?");
                $stmt->execute([$name, $phone, $address, $_SESSION['email']]);
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
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE email = ?");
            $stmt->execute([$_SESSION['email']]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password'])) {
                if (strlen($new_password) < 6) {
                    $_SESSION['profile_error'] = "Parola nouă trebuie să aibă cel puțin 6 caractere!";
                } elseif ($new_password !== $confirm_password) {
                    $_SESSION['profile_error'] = "Parolele nu se potrivesc!";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                    $stmt->execute([$hashed_password, $_SESSION['email']]);
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

if (!isset($_SESSION['email'])) {
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
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="user_page.css">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
    <link rel="manifest" href="images/favicon/site.webmanifest">
</head>

<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="logo">
                <i class='bx bxs-store-alt'></i>
                <span>Maison Lure</span>
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn-outline-light"><i class='bx bx-shopping-bag'></i> Magazin</a>
                <a href="logout.php" class="btn-outline-light"><i class='bx bx-log-out'></i> Deconectare</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert-custom alert-success" style="margin-bottom: 1.5rem;">
                <i class='bx bx-check-circle'></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert-custom alert-error" style="margin-bottom: 1.5rem;">
                <i class='bx bx-error-circle'></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">

            <div class="card">
                <div class="card-title">
                    <i class='bx bx-user-circle'></i>
                    <span>Profilul meu</span>
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

            <div class="card">
                <div class="card-title">
                    <i class='bx bx-edit-alt'></i>
                    <span>Actualizează datele</span>
                </div>
                <form method="POST" style="flex: 1; display: flex; flex-direction: column;">
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

            <div class="card" id="current-cart-card">
                <div class="card-title">
                    <i class='bx bx-cart'></i>
                    <span>Coșul meu curent</span>
                </div>
                <div id="cart-items-list" style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                    <div class="cart-empty-placeholder" style="text-align: center; padding: 1rem; color: #718096;">
                        <i class='bx bx-cart-alt' style="font-size: 2rem;"></i>
                        <p>Coșul este gol. Adaugă produse din magazin.</p>
                    </div>
                </div>
                <div class="cart-summary"
                    style="background: #f8f9fa; border-radius: 0.75rem; padding: 0.75rem; margin: 0.5rem 0;">
                    <span>Total produse: <strong id="cart-total-qty">0</strong></span>
                    <span>Total: <strong id="cart-total-price">0</strong> MDL</span>
                </div>
                <button id="clear-cart-btn" class="btn-primary" style="background: #030303; margin-top: 0.5rem;">
                    <i class='bx bx-trash'></i> Golește coșul
                </button>
                <button id="place-order-btn" class="btn-primary" style="background: #764ba2; margin-top: 0.5rem;">
                    <i class='bx bx-check-circle'></i> Plasează comanda
                </button>
            </div>

            <div class="card">
                <div class="card-title">
                    <i class='bx bx-purchase-tag'></i>
                    <span>Comenzile mele</span>
                </div>
                <div id="orders-list">
                    <div class="orders-empty">
                        <i class='bx bx-package'></i>
                        <h3>Se încarcă...</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card full-width">
            <div class="card-title">
                <i class='bx bx-lock-alt'></i>
                <span>Schimbă parola</span>
            </div>
            <form method="POST">
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
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let cart = JSON.parse(localStorage.getItem("cart")) || [];

            function displayCart() {
                const container = document.getElementById("cart-items-list");
                const totalQtySpan = document.getElementById("cart-total-qty");
                const totalPriceSpan = document.getElementById("cart-total-price");

                if (!container) return;

                if (cart.length === 0) {
                    container.innerHTML = `<div class="cart-empty-placeholder" style="text-align: center; padding: 1rem; color: #718096;">
                                        <i class='bx bx-cart-alt' style="font-size: 2rem;"></i>
                                        <p>Coșul este gol. Adaugă produse din magazin.</p>
                                    </div>`;
                    totalQtySpan.textContent = "0";
                    totalPriceSpan.textContent = "0";
                    return;
                }

                let totalQty = 0;
                let totalPrice = 0;
                let html = '<ul style="list-style: none; padding: 0; margin: 0;">';

                cart.forEach((item, index) => {
                    const qty = item.quantity || 1;
                    const price = item.price || 0;
                    const itemTotal = qty * price;
                    totalQty += qty;
                    totalPrice += itemTotal;
                    const productName = item.name || "Produs";

                    html += `
                <li style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding: 10px 0;">
                    <div style="display: flex; align-items: center; gap: 12px; flex: 2;">
                        <img src="${item.image || ''}" alt="${productName.replace(/"/g, '&quot;')}" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px;">
                        <div>
                            <div style="font-weight: 600;">${productName.replace(/</g, '&lt;')}</div>
                            <div style="font-size: 0.8rem; color: #4a5568;">${price} MDL × ${qty}</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button class="cart-decrease-qty" data-index="${index}" style="background: #e2e8f0; border: none; border-radius: 6px; width: 28px; height: 28px; cursor: pointer;">-</button>
                        <span style="min-width: 20px; text-align: center;">${qty}</span>
                        <button class="cart-increase-qty" data-index="${index}" style="background: #e2e8f0; border: none; border-radius: 6px; width: 28px; height: 28px; cursor: pointer;">+</button>
                        <button class="cart-remove-item" data-index="${index}" style="background: #dc3545; color: white; border: none; border-radius: 6px; width: 28px; height: 28px; cursor: pointer;">×</button>
                    </div>
                </li>
            `;
                });

                html += '</ul>';
                container.innerHTML = html;
                totalQtySpan.textContent = totalQty;
                totalPriceSpan.textContent = totalPrice.toFixed(0);
            }

            function saveAndRefresh() {
                localStorage.setItem("cart", JSON.stringify(cart));
                displayCart();
            }

            document.getElementById("cart-items-list")?.addEventListener("click", function (e) {
                const target = e.target;
                const indexAttr = target.getAttribute("data-index");
                if (indexAttr === null) return;

                const idx = parseInt(indexAttr, 10);
                if (isNaN(idx)) return;

                if (target.classList.contains("cart-decrease-qty")) {
                    if (cart[idx].quantity > 1) {
                        cart[idx].quantity--;
                    } else {
                        cart.splice(idx, 1);
                    }
                    saveAndRefresh();
                } else if (target.classList.contains("cart-increase-qty")) {
                    cart[idx].quantity = (cart[idx].quantity || 0) + 1;
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
                    alert("Coșul este gol. Adaugă produse înainte de a plasa o comandă.");
                    return;
                }

                fetch("place_order.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ cart: cart })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        cart = [];
                        localStorage.setItem("cart", JSON.stringify(cart));
                        displayCart();
                        loadOrders();
                    } else {
                        alert("Eroare: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("A apărut o eroare la plasarea comenzii.");
                });
            });

            function loadOrders() {
                fetch("get_orders.php")
                    .then(response => response.json())
                    .then(data => {
                        const ordersContainer = document.getElementById("orders-list");
                        if (!ordersContainer) return;

                        if (data.orders && data.orders.length > 0) {
                            let html = `<div style="max-height: 400px; overflow-y: auto;">`;
                            data.orders.forEach(order => {
                                html += `
                                    <div style="border-bottom: 1px solid #e2e8f0; padding: 12px 0; margin-bottom: 10px;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <strong>Comanda #${order.id}</strong>
                                            <span>${order.order_date}</span>
                                        </div>
                                        <div>Total: ${order.total_amount} MDL</div>
                                        <div style="font-size: 0.85rem; margin-top: 5px;">
                                            Produse:
                                            <ul style="margin-left: 20px;">
                                `;
                                order.items.forEach(item => {
                                    html += `<li>${item.product_name} × ${item.quantity} – ${item.product_price} MDL</li>`;
                                });
                                html += `</ul></div></div>`;
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