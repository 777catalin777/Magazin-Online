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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars(lang('site_title') ?? 'Maison Lure') ?> | Profil Utilizator</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
        }

        .profile-container {
            max-width: 1280px;
            margin: 1rem auto;
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr;
            transition: all 0.2s ease;
        }

        .profile-sidebar {
            background: linear-gradient(135deg, #2c3e66, #1a2a44);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }

        .avatar {
            width: 110px;
            height: 110px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.4rem;
            backdrop-filter: blur(4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s;
        }

        .profile-sidebar h2 {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            word-break: break-word;
        }

        .profile-sidebar p {
            opacity: 0.85;
            font-size: 0.95rem;
            margin-bottom: 0.3rem;
            word-break: break-word;
        }

        .profile-sidebar .btn-sidebar {
            margin-top: 1.8rem;
            width: 100%;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .profile-sidebar .btn-sidebar:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .btn-danger-sidebar {
            background: rgba(220, 53, 69, 0.85);
            border: none;
        }

        .btn-danger-sidebar:hover {
            background: #dc3545;
        }

        .profile-main {
            padding: 2rem 1.8rem;
            background: #ffffff;
        }

        .profile-main h1 {
            font-size: 1.9rem;
            margin-bottom: 1.2rem;
            font-weight: 600;
            color: #1e2a3a;
            border-left: 5px solid #428ed6;
            padding-left: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px 18px;
            border-radius: 14px;
            margin-bottom: 1.8rem;
            border-left: 5px solid #28a745;
            font-weight: 500;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 18px;
            border-radius: 14px;
            margin-bottom: 1.8rem;
            border-left: 5px solid #dc3545;
            font-weight: 500;
        }

        .nav-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e0e7ed;
            padding-bottom: 0.5rem;
        }

        .tab {
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            border-radius: 40px;
            font-weight: 600;
            color: #4a5b6e;
            transition: all 0.2s;
            background: #f1f5f9;
            margin-bottom: 0.3rem;
        }

        .tab.active {
            background: #428ed6;
            color: white;
            box-shadow: 0 5px 12px rgba(66, 142, 214, 0.3);
        }

        .tab:hover:not(.active) {
            background: #e2e8f0;
            color: #1e2a3a;
        }

        .info-card {
            background: #f9fbfd;
            padding: 1.8rem;
            border-radius: 24px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            border: 1px solid #eef2f6;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.4rem;
            color: #1e2a3a;
            position: relative;
            display: inline-block;
        }

        .form-group {
            margin-bottom: 1.4rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            font-size: 1rem;
            transition: 0.2s;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #428ed6;
            box-shadow: 0 0 0 3px rgba(66, 142, 214, 0.2);
        }

        .form-group input:disabled {
            background: #eef2f6;
            cursor: not-allowed;
        }

        .btn {
            background: linear-gradient(135deg, #428ed6, #2c6ea0);
            color: white;
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.25s;
            display: inline-block;
            width: auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(66, 142, 214, 0.3);
            background: linear-gradient(135deg, #2c6ea0, #1e4e76);
        }

        .pref-checkbox {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 1rem;
        }

        .pref-checkbox input {
            width: 20px;
            height: 20px;
            accent-color: #428ed6;
        }

        @media (min-width: 900px) {
            .profile-container {
                grid-template-columns: 320px 1fr;
            }
            .profile-sidebar {
                border-radius: 28px 0 0 28px;
            }
            .profile-main {
                padding: 2rem 2.2rem;
            }
        }

        @media (max-width: 899px) {
            body {
                padding: 0.5rem;
            }
            .profile-container {
                border-radius: 24px;
            }
            .profile-sidebar {
                padding: 1.8rem 1rem;
            }
            .avatar {
                width: 90px;
                height: 90px;
                font-size: 2.8rem;
            }
            .profile-sidebar h2 {
                font-size: 1.4rem;
            }
            .profile-main {
                padding: 1.5rem;
            }
            .profile-main h1 {
                font-size: 1.7rem;
            }
            .section-title {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 640px) {
            .nav-tabs {
                justify-content: center;
                gap: 0.4rem;
            }
            .tab {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            .info-card {
                padding: 1.2rem;
            }
            .btn {
                width: 100%;
                text-align: center;
                padding: 0.8rem;
            }
            .form-group input {
                padding: 0.8rem;
            }
            .profile-sidebar .btn-sidebar {
                padding: 0.7rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .profile-main h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            .section-title {
                font-size: 1.2rem;
            }
            .avatar {
                width: 75px;
                height: 75px;
                font-size: 2.4rem;
            }
            .tab {
                padding: 0.45rem 0.9rem;
                font-size: 0.85rem;
            }
            .alert-success, .alert-error {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 380px) {
            .profile-main {
                padding: 1rem;
            }
            .tab {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
            .info-card {
                padding: 1rem;
            }
            .form-group input {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">

        <div class="profile-sidebar">
            <div class="avatar">
                👤
            </div>
            <h2><?= htmlspecialchars($_SESSION['name'] ?? 'Utilizator') ?></h2>
            <p><?= htmlspecialchars($_SESSION['email']) ?></p>
            <p style="margin-top: 0.5rem; font-size: 0.85rem; opacity: 0.8;">
                Rol: <?= htmlspecialchars($_SESSION['role'] ?? 'user') ?>
            </p>
            
            <button onclick="window.location.href='logout.php'" class="btn btn-sidebar btn-danger-sidebar" style="margin-top: 2rem;">
                <?= htmlspecialchars(lang('logout') ?? 'Deconectare') ?>
            </button>
            <button onclick="window.location.href='index.php'" class="btn btn-sidebar" style="margin-top: 0.8rem;">
                ← Înapoi la magazin
            </button>
        </div>

        <div class="profile-main">
            <h1>Profilul Meu</h1>
            
            <?php if ($success): ?>
                <div class="alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="nav-tabs">
                <div class="tab active" onclick="showTab(0)">Informații Personale</div>
                <div class="tab" onclick="showTab(1)">Comenzi</div>
                <div class="tab" onclick="showTab(2)">Setări Cont</div>
            </div>

            <div id="tab0" class="tab-content">
                <div class="info-card">
                    <h3 class="section-title">Date Personale</h3>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label>Nume complet</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" readonly disabled>
                        </div>
                        <div class="form-group">
                            <label>Telefon</label>
                            <input type="tel" name="phone" placeholder="Introduceți numărul de telefon" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Adresă</label>
                            <input type="text" name="address" placeholder="Strada, număr, bloc..." value="<?= htmlspecialchars($_SESSION['address'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn">Salvează Modificările</button>
                    </form>
                </div>
            </div>

            <div id="tab1" class="tab-content" style="display: none;">
                <div class="info-card">
                    <h3 class="section-title">Istoric Comenzi</h3>
                    <p style="color: #4a627a; font-style: italic;">Momentan nu aveți comenzi.</p>
                    <p style="margin-top: 1.2rem;">
                        <strong>0</strong> comenzi totale • Total cheltuit: <strong>0 lei</strong>
                    </p>
                    <button onclick="window.location.href='index.php'" class="btn" style="margin-top: 1.5rem;">
                        Continuă cumpărăturile
                    </button>
                </div>
            </div>

            <div id="tab2" class="tab-content" style="display: none;">
                <div class="info-card">
                    <h3 class="section-title">Schimbă Parola</h3>
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label>Parola curentă</label>
                            <input type="password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label>Parola nouă</label>
                            <input type="password" name="new_password" required autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label>Confirmă parola nouă</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn">Schimbă Parola</button>
                    </form>
                    
                    <h3 class="section-title" style="margin-top: 2rem;">Preferințe</h3>
                    <div class="pref-checkbox">
                        <input type="checkbox" checked id="notif_email"> 
                        <label for="notif_email">Primește notificări prin email</label>
                    </div>
                    <div class="pref-checkbox">
                        <input type="checkbox" checked id="newsletter"> 
                        <label for="newsletter">Newsletter cu noutăți și promoții</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(n) {

            document.querySelectorAll('.tab-content').forEach((el, i) => {
                el.style.display = i === n ? 'block' : 'none';
            });

            document.querySelectorAll('.tab').forEach((el, i) => {
                if (i === n) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>