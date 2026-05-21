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
            background: linear-gradient(135deg, #f5f7fc 0%, #e9eef5 100%);
            font-family: 'Segoe UI', 'Poppins', Tahoma, Geneva, Verdana, sans-serif;
            padding: 0;
            min-height: 100vh;
        }

        /* Modern navbar */
        .profile-navbar {
            background: linear-gradient(135deg, #2c3e66, #1a2a44);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .logo-area h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .logo-area h2 i {
            margin-right: 8px;
            color: #ffd966;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
        }

        .nav-btn:hover {
            background: #6e8efb;
            transform: translateY(-2px);
        }

        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Profile grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2rem;
        }

        /* Sidebar card */
        .profile-sidebar-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transition: transform 0.3s ease;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .profile-sidebar-card:hover {
            transform: translateY(-5px);
        }

        .sidebar-header {
            background: linear-gradient(135deg, #428ed6, #2c6ea0);
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
        }

        .avatar-circle {
            width: 110px;
            height: 110px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            backdrop-filter: blur(4px);
            border: 3px solid rgba(255, 255, 255, 0.5);
        }

        .sidebar-header h3 {
            font-size: 1.4rem;
            margin-bottom: 0.25rem;
        }

        .sidebar-header p {
            opacity: 0.9;
            font-size: 0.85rem;
        }

        .sidebar-body {
            padding: 1.5rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #eef2f6;
        }

        .info-row i {
            width: 28px;
            font-size: 1.3rem;
            color: #428ed6;
        }

        .info-row .info-label {
            font-weight: 600;
            color: #4a5b6e;
            width: 80px;
        }

        .info-row .info-value {
            color: #1e2a3a;
            word-break: break-word;
            flex: 1;
        }

        .sidebar-actions {
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-sidebar-action {
            background: #f1f5f9;
            border: none;
            padding: 12px;
            border-radius: 28px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #2c3e66;
            text-decoration: none;
        }

        .btn-sidebar-action:hover {
            background: #e2e8f0;
            transform: translateX(5px);
        }

        .btn-logout {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-logout:hover {
            background: #fecaca;
        }

        /* Main content */
        .profile-content {
            background: white;
            border-radius: 32px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .content-header {
            padding: 1.8rem 2rem;
            background: #fafcff;
            border-bottom: 1px solid #eef2f6;
        }

        .content-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #2c3e66, #428ed6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.25rem;
        }

        .content-header p {
            color: #6c7a8e;
        }

        /* Tabs */
        .profile-tabs {
            display: flex;
            gap: 0.5rem;
            padding: 0 2rem;
            border-bottom: 1px solid #eef2f6;
            background: white;
        }

        .tab-btn {
            padding: 1rem 1.8rem;
            background: none;
            border: none;
            font-weight: 600;
            font-size: 1rem;
            color: #6c7a8e;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn i {
            font-size: 1.2rem;
        }

        .tab-btn.active {
            color: #428ed6;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #428ed6, #6e8efb);
            border-radius: 3px 3px 0 0;
        }

        .tab-btn:hover:not(.active) {
            color: #2c3e66;
            background: #f8fafd;
        }

        .tab-pane {
            display: none;
            padding: 2rem;
            animation: fadeIn 0.3s ease;
        }

        .tab-pane.active-pane {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form styling */
        .form-card {
            background: #f9fbfd;
            border-radius: 24px;
            padding: 1.8rem;
            margin-bottom: 2rem;
            border: 1px solid #eef2f6;
        }

        .form-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1e2a3a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .form-group input {
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

        .btn-primary {
            background: linear-gradient(135deg, #428ed6, #2c6ea0);
            color: white;
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(66, 142, 214, 0.3);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 14px 20px;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border-left: 5px solid #28a745;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 14px 20px;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            border-left: 5px solid #dc3545;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-orders {
            text-align: center;
            padding: 3rem;
            background: #f9fbfd;
            border-radius: 24px;
            color: #6c7a8e;
        }

        .empty-orders i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .pref-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
            padding: 10px 0;
        }

        .pref-checkbox input {
            width: 20px;
            height: 20px;
            accent-color: #428ed6;
        }

        @media (max-width: 900px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .profile-sidebar-card {
                position: static;
            }
            .profile-tabs {
                overflow-x: auto;
                padding: 0 1rem;
            }
            .tab-btn {
                padding: 0.8rem 1.2rem;
                white-space: nowrap;
            }
            .tab-pane {
                padding: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .profile-navbar {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            .main-container {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            .form-card {
                padding: 1.2rem;
            }
            .btn-primary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="profile-navbar">
    <div class="logo-area">
        <h2><i class='bx bxs-store'></i> Maison Lure</h2>
    </div>
    <div class="nav-links">
        <a href="index.php" class="nav-btn"><i class='bx bx-shopping-bag'></i> Magazin</a>
        <a href="logout.php" class="nav-btn" style="background: rgba(220,53,69,0.8);"><i class='bx bx-log-out'></i> Deconectare</a>
    </div>
</div>

<div class="main-container">
    <div class="profile-grid">
        <aside class="profile-sidebar-card">
            <div class="sidebar-header">
                <div class="avatar-circle">
                    <i class='bx bxs-user-circle'></i>
                </div>
                <h3><?= htmlspecialchars($_SESSION['name'] ?? 'Utilizator') ?></h3>
                <p><?= htmlspecialchars($_SESSION['email']) ?></p>
                <span style="display: inline-block; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; margin-top: 8px;">
                    <?= htmlspecialchars($_SESSION['role'] ?? 'user') ?>
                </span>
            </div>
            <div class="sidebar-body">
                <div class="info-row">
                    <i class='bx bx-phone'></i>
                    <span class="info-label">Telefon:</span>
                    <span class="info-value"><?= htmlspecialchars($_SESSION['phone'] ?? 'Neintrodus') ?></span>
                </div>
                <div class="info-row">
                    <i class='bx bx-map'></i>
                    <span class="info-label">Adresă:</span>
                    <span class="info-value"><?= htmlspecialchars($_SESSION['address'] ?? 'Neintrodusă') ?></span>
                </div>
                <div class="sidebar-actions">
                    <button onclick="document.querySelector('.tab-btn:nth-child(1)').click();" class="btn-sidebar-action">
                        <i class='bx bx-edit-alt'></i> Editează profil
                    </button>
                    <button onclick="document.querySelector('.tab-btn:nth-child(2)').click();" class="btn-sidebar-action">
                        <i class='bx bx-package'></i> Istoric comenzi
                    </button>
                    <button onclick="document.querySelector('.tab-btn:nth-child(3)').click();" class="btn-sidebar-action">
                        <i class='bx bx-lock-alt'></i> Setări cont
                    </button>
                    <a href="logout.php" class="btn-sidebar-action btn-logout">
                        <i class='bx bx-log-out-circle'></i> Deconectare
                    </a>
                </div>
            </div>
        </aside>

        <div class="profile-content">
            <div class="content-header">
                <h1><i class='bx bx-user'></i> Contul meu</h1>
                <p>Gestionează-ți datele personale, parole și preferințe</p>
            </div>

            <?php if ($success): ?>
                <div class="alert-success" style="margin: 1rem 2rem 0 2rem;">
                    <i class='bx bx-check-circle'></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert-error" style="margin: 1rem 2rem 0 2rem;">
                    <i class='bx bx-error-circle'></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="profile-tabs">
                <button class="tab-btn active" data-tab="tab1"><i class='bx bx-user'></i> Informații personale</button>
                <button class="tab-btn" data-tab="tab2"><i class='bx bx-receipt'></i> Comenzile mele</button>
                <button class="tab-btn" data-tab="tab3"><i class='bx bx-shield-quarter'></i> Securitate & Preferințe</button>
            </div>

            <div id="tab1" class="tab-pane active-pane">
                <div class="form-card">
                    <div class="form-title">
                        <i class='bx bx-id-card'></i> Date personale
                    </div>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label>Nume complet</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Adresă email</label>
                            <input type="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" disabled>
                            <small style="color:#6c7a8e;">Emailul nu poate fi modificat</small>
                        </div>
                        <div class="form-group">
                            <label>Număr de telefon</label>
                            <input type="tel" name="phone" placeholder="+373 6X XXX XXX" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Adresă de livrare</label>
                            <input type="text" name="address" placeholder="Strada, numărul, orașul" value="<?= htmlspecialchars($_SESSION['address'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn-primary"><i class='bx bx-save'></i> Salvează modificările</button>
                    </form>
                </div>
            </div>

            <div id="tab2" class="tab-pane">
                <div class="empty-orders">
                    <i class='bx bx-package'></i>
                    <h3>Nu ai plasat nicio comandă încă</h3>
                    <p>Descoperă produsele noastre și bucură-te de shopping!</p>
                    <a href="index.php" class="btn-primary" style="display: inline-flex; margin-top: 1rem; text-decoration: none;">
                        <i class='bx bx-cart-add'></i> Începe cumpărăturile
                    </a>
                </div>
            </div>

            <div id="tab3" class="tab-pane">
                <div class="form-card">
                    <div class="form-title">
                        <i class='bx bx-key'></i> Schimbă parola
                    </div>
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label>Parola curentă</label>
                            <input type="password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label>Parola nouă</label>
                            <input type="password" name="new_password" required autocomplete="new-password">
                            <small>Minim 6 caractere</small>
                        </div>
                        <div class="form-group">
                            <label>Confirmă parola nouă</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn-primary"><i class='bx bx-lock-open'></i> Actualizează parola</button>
                    </form>
                </div>

                <div class="form-card">
                    <div class="form-title">
                        <i class='bx bx-bell'></i> Preferințe notificări
                    </div>
                    <div class="pref-checkbox">
                        <input type="checkbox" checked id="notif_email"> 
                        <label for="notif_email"><strong>Notificări prin email</strong> - Oferte și actualizări comenzi</label>
                    </div>
                    <div class="pref-checkbox">
                        <input type="checkbox" checked id="newsletter"> 
                        <label for="newsletter"><strong>Newsletter săptămânal</strong> - Noutăți și promoții exclusive</label>
                    </div>
                    <button class="btn-primary" onclick="alert('Preferințele au fost salvate (demo)');"><i class='bx bx-check-double'></i> Salvează preferințele</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active-pane'));
            document.getElementById(tabId).classList.add('active-pane');
        });
    });
</script>

</body>
</html>