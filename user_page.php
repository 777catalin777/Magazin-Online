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
                $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
    <link rel="manifest" href="images/favicon/site.webmanifest">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dashboard-container {
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            font-size: 2rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-outline-light {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.6rem 1.5rem;
            border-radius: 2rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            transform: translateY(-2px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.25);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.75rem;
        }

        .card-title i {
            color: #667eea;
        }

        /* Profile info */
        .profile-avatar {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }

        .info-row {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
            gap: 1rem;
        }

        .info-label {
            font-weight: 600;
            width: 100px;
            color: #4a5568;
        }

        .info-value {
            flex: 1;
            color: #2d3748;
            word-break: break-word;
        }

        .role-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: #4a5568;
        }

        .input-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-box input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            color: #1a1a2e;
        }

        .input-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .input-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: #a0aec0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.4);
        }

        .alert-custom {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: #e6fffa;
            color: #234e52;
            border-left: 4px solid #38b2ac;
        }

        .alert-error {
            background: #fff5f5;
            color: #c53030;
            border-left: 4px solid #fc8181;
        }

        .orders-empty {
            text-align: center;
            padding: 2rem;
            background: #f7fafc;
            border-radius: 1rem;
            margin-top: 2rem;
        }

        .orders-empty i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }

        .orders-empty h3 {
            font-size: 1.2rem;
            color: #4a5568;
            margin-bottom: 0.5rem;
        }

        .btn-link {
            display: inline-block;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .full-width {
            grid-column: span 2;
        }

        @media (max-width: 968px) {
            body {
                padding: 1rem;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .full-width {
                grid-column: span 1;
            }
            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="logo">
            <i class='bx bxs-store-alt'></i>
            <span>MAISON LURE</span>
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
            <div class="info-row">
                <div class="info-label">Rol</div>
                <div class="info-value"><span class="role-badge"><?= htmlspecialchars($_SESSION['role'] ?? 'user') ?></span></div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">
                <i class='bx bx-edit-alt'></i>
                <span>Actualizează datele</span>
            </div>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="input-box">
                    <i class='bx bx-user'></i>
                    <input type="text" name="name" placeholder="Nume complet" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                </div>
                <div class="input-box">
                    <i class='bx bx-phone'></i>
                    <input type="tel" name="phone" placeholder="Număr de telefon" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
                </div>
                <div class="input-box">
                    <i class='bx bx-map'></i>
                    <input type="text" name="address" placeholder="Adresă de livrare" value="<?= htmlspecialchars($_SESSION['address'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-primary"><i class='bx bx-save'></i> Salvează modificările</button>
            </form>
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

        <div class="card full-width">
            <div class="card-title">
                <i class='bx bx-purchase-tag'></i>
                <span>Comenzile mele</span>
            </div>
            <div class="orders-empty">
                <i class='bx bx-package'></i>
                <h3>Nu ai nicio comandă încă</h3>
                <p>Descoperă colecția noastră și completează-ți primul buchet de vise.</p>
                <a href="index.php" class="btn-link"><i class='bx bx-cart'></i> Explorează magazinul</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>