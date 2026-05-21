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
    <title><?= htmlspecialchars(lang('site_title') ?? 'Maison Lure') ?> | DASH//FRACTAL</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0c12;
            font-family: 'Space Grotesk', monospace;
            color: #eef5ff;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 40%, rgba(30, 40, 70, 0.6), rgba(0, 0, 0, 0.9));
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(0deg, rgba(66, 142, 214, 0.03) 0px, rgba(66, 142, 214, 0.03) 2px, transparent 2px, transparent 6px);
            pointer-events: none;
            z-index: -1;
            animation: scan 20s linear infinite;
        }

        @keyframes scan {
            0% { transform: translateY(0); }
            100% { transform: translateY(100px); }
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.3;
            z-index: -1;
            animation: float 20s infinite alternate ease-in-out;
        }
        .orb-1 { width: 40vw; height: 40vw; background: #428ed6; top: -10vh; left: -10vw; }
        .orb-2 { width: 50vw; height: 50vw; background: #a777e3; bottom: -20vh; right: -15vw; animation-duration: 25s; }
        .orb-3 { width: 30vw; height: 30vw; background: #ff6b6b; top: 50%; left: 60%; animation-duration: 18s; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(5%, 5%) scale(1.1); }
        }

        .fractal-container {
            max-width: 1600px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 2;
        }

        .glitch-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 3rem;
            background: rgba(10, 12, 18, 0.6);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 0.8rem 2rem;
            border: 1px solid rgba(66, 142, 214, 0.3);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #fff, #6e8efb);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo i {
            margin-right: 8px;
            color: #428ed6;
            background: none;
            -webkit-background-clip: unset;
            background-clip: unset;
            color: #6e8efb;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(66,142,214,0.4);
            padding: 0.6rem 1.4rem;
            border-radius: 40px;
            color: #eef5ff;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            backdrop-filter: blur(4px);
        }

        .nav-btn:hover {
            background: #428ed6;
            border-color: #428ed6;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(66,142,214,0.4);
        }

        .asymmetric-grid {
            display: grid;
            grid-template-columns: 1fr 2.2fr;
            gap: 2rem;
        }

        .profile-vortex {
            background: rgba(15, 20, 30, 0.55);
            backdrop-filter: blur(16px);
            border-radius: 48px;
            border: 1px solid rgba(255,255,255,0.15);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            transform: rotate(0.5deg);
        }

        .profile-vortex:hover {
            transform: rotate(0deg) translateY(-8px);
            border-color: rgba(66,142,214,0.5);
            box-shadow: 0 20px 35px -12px rgba(0,0,0,0.5);
        }

        .vortex-header {
            background: linear-gradient(125deg, #1e2a3a, #0f1722);
            padding: 2rem 1.5rem;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }

        .avatar-glitch {
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #428ed6, #a777e3);
            border-radius: 37% 63% 70% 30% / 43% 45% 55% 57%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            animation: morph 8s infinite alternate;
            border: 2px solid rgba(255,255,255,0.3);
        }

        @keyframes morph {
            0% { border-radius: 37% 63% 70% 30% / 43% 45% 55% 57%; }
            100% { border-radius: 70% 30% 37% 63% / 55% 57% 43% 45%; }
        }

        .vortex-header h3 {
            font-size: 1.6rem;
            font-weight: 600;
        }

        .role-badge {
            display: inline-block;
            background: rgba(0,0,0,0.5);
            padding: 4px 14px;
            border-radius: 40px;
            font-size: 0.7rem;
            letter-spacing: 1px;
            margin-top: 8px;
        }

        .vortex-body {
            padding: 1.8rem;
        }

        .info-fractal {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
        }
        .info-item i {
            width: 32px;
            font-size: 1.3rem;
            color: #6e8efb;
        }
        .info-label {
            font-weight: 500;
            opacity: 0.7;
            width: 80px;
        }
        .info-value {
            word-break: break-word;
            flex:1;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .action-btn {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 12px;
            border-radius: 28px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #eef5ff;
        }
        .action-btn:hover {
            background: rgba(66,142,214,0.3);
            border-color: #428ed6;
            transform: translateX(5px);
        }
        .logout-btn {
            background: rgba(220,53,69,0.2);
            border-color: rgba(220,53,69,0.5);
        }
        .logout-btn:hover {
            background: rgba(220,53,69,0.6);
        }

        .right-panel {
            background: rgba(10, 12, 18, 0.5);
            backdrop-filter: blur(16px);
            border-radius: 48px;
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
        }

        .panel-header {
            padding: 1.5rem 2rem;
            background: rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .panel-header h1 {
            font-size: 2rem;
            font-weight: 600;
            background: linear-gradient(135deg, #fff, #a0c0ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .panel-header p {
            opacity: 0.6;
            font-size: 0.9rem;
        }

        .neo-tabs {
            display: flex;
            gap: 0.2rem;
            padding: 0 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.2);
        }
        .tab-neo {
            background: none;
            border: none;
            padding: 1rem 1.8rem;
            font-family: 'Space Grotesk', monospace;
            font-weight: 600;
            font-size: 0.95rem;
            color: #b0c4de;
            cursor: pointer;
            transition: 0.2s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-neo i { font-size: 1.2rem; }
        .tab-neo.active {
            color: white;
        }
        .tab-neo.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #428ed6, #a777e3);
            border-radius: 3px 3px 0 0;
            box-shadow: 0 -2px 8px rgba(66,142,214,0.5);
        }
        .tab-neo:hover:not(.active) {
            color: white;
            background: rgba(255,255,255,0.05);
        }

        .pane {
            display: none;
            padding: 2rem;
            animation: fadeSlide 0.4s ease;
        }
        .pane.active-pane {
            display: block;
        }
        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .glass-card {
            background: rgba(20, 30, 45, 0.5);
            border-radius: 28px;
            padding: 1.8rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .glass-card h3 {
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.3rem;
        }
        .input-group {
            margin-bottom: 1.2rem;
        }
        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
        }
        .input-group input {
            width: 100%;
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 12px 16px;
            border-radius: 24px;
            color: white;
            font-family: 'Space Grotesk', monospace;
            transition: 0.2s;
        }
        .input-group input:focus {
            outline: none;
            border-color: #428ed6;
            box-shadow: 0 0 0 2px rgba(66,142,214,0.3);
        }
        .input-group input:disabled {
            opacity: 0.6;
        }
        .btn-glow {
            background: linear-gradient(95deg, #428ed6, #2c6ea0);
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 700;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-glow:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(66,142,214,0.5);
        }
        .alert-custom {
            padding: 12px 18px;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        .alert-success {
            background: rgba(40,167,69,0.2);
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: rgba(220,53,69,0.2);
            border-left: 4px solid #dc3545;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: rgba(0,0,0,0.3);
            border-radius: 28px;
        }
        .pref-check {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1rem 0;
        }

        @media (max-width: 900px) {
            .asymmetric-grid {
                grid-template-columns: 1fr;
            }
            .glitch-header {
                flex-direction: column;
                text-align: center;
                border-radius: 40px;
            }
            .neo-tabs {
                overflow-x: auto;
                padding: 0 1rem;
            }
            .tab-neo {
                white-space: nowrap;
            }
        }
        @media (max-width: 550px) {
            .fractal-container {
                padding: 0 1rem;
            }
            .pane {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="fractal-container">
    <div class="glitch-header">
        <div class="logo"><i class='bx bx-fingerprint'></i> MAISON//LURE</div>
        <div class="nav-links">
            <a href="index.php" class="nav-btn"><i class='bx bx-grid-alt'></i> SHOP</a>
            <a href="logout.php" class="nav-btn"><i class='bx bx-exit'></i> EXIT</a>
        </div>
    </div>

    <div class="asymmetric-grid">
        <div class="profile-vortex">
            <div class="vortex-header">
                <div class="avatar-glitch">
                    <i class='bx bx-bot'></i>
                </div>
                <h3><?= htmlspecialchars($_SESSION['name'] ?? 'STRANGER') ?></h3>
                <div class="role-badge">#<?= htmlspecialchars($_SESSION['role'] ?? 'user') ?>_access</div>
                <p style="font-size:0.75rem; margin-top:8px;"><?= htmlspecialchars($_SESSION['email']) ?></p>
            </div>
            <div class="vortex-body">
                <div class="info-fractal">
                    <div class="info-item"><i class='bx bx-devices'></i><span class="info-label">CONTACT</span><span class="info-value"><?= htmlspecialchars($_SESSION['phone'] ?? '⚡ nedefinit') ?></span></div>
                    <div class="info-item"><i class='bx bx-map-pin'></i><span class="info-label">COORD</span><span class="info-value"><?= htmlspecialchars($_SESSION['address'] ?? '🔮 necunoscută') ?></span></div>
                </div>
                <div class="action-buttons">
                    <button class="action-btn" data-nav="tab1"><i class='bx bx-user-voice'></i> EDIT PROFILE</button>
                    <button class="action-btn" data-nav="tab2"><i class='bx bx-receipt'></i> ORDER GHOST</button>
                    <button class="action-btn" data-nav="tab3"><i class='bx bx-cog'></i> CIPHER SETTINGS</button>
                    <a href="logout.php" class="action-btn logout-btn"><i class='bx bx-log-out-circle'></i> DECONECTARE</a>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="panel-header">
                <h1><i class='bx bx-data'></i> User</h1>
                <p>Interfață haotică / gestionează-ți realitatea</p>
            </div>
            <?php if ($success): ?>
                <div class="alert-custom alert-success" style="margin: 1rem 2rem 0 2rem;"><i class='bx bx-check-shield'></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-custom alert-error" style="margin: 1rem 2rem 0 2rem;"><i class='bx bx-error'></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="neo-tabs">
                <button class="tab-neo active" data-tab="tab1"><i class='bx bx-id-card'></i> Personal</button>
                <button class="tab-neo" data-tab="tab2"><i class='bx bx-purchase-tag'></i> Comenzi</button>
                <button class="tab-neo" data-tab="tab3"><i class='bx bx-lock'></i> Security</button>
            </div>

            <!-- tab1 -->
            <div id="tab1" class="pane active-pane">
                <div class="glass-card">
                    <h3><i class='bx bx-edit-alt'></i> Actualizează datele</h3>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="input-group">
                            <label>Nume</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" disabled>
                        </div>
                        <div class="input-group">
                            <label>Telefon</label>
                            <input type="tel" name="phone" placeholder="+37300000000" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
                        </div>
                        <div class="input-group">
                            <label>Adresă livrare</label>
                            <input type="text" name="address" placeholder="Strada, oraș" value="<?= htmlspecialchars($_SESSION['address'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn-glow"><i class='bx bx-save'></i> Salvează mutațiile</button>
                    </form>
                </div>
            </div>

            <div id="tab2" class="pane">
                <div class="empty-state">
                    <i class='bx bx-package' style="font-size: 4rem; opacity:0.5;"></i>
                    <h3>Zero comenzi</h3>
                    <p>Niciun artifact în istoric. Timpul să umpli vidul.</p>
                    <a href="index.php" class="btn-glow" style="display: inline-block; margin-top: 1rem; text-decoration: none;"><i class='bx bx-cart'></i> EXPLORE SHOP</a>
                </div>
            </div>

            <div id="tab3" class="pane">
                <div class="glass-card">
                    <h3><i class='bx bx-key'></i> Schimbă cheia de acces</h3>
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="input-group">
                            <label>Parola curentă</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="input-group">
                            <label>Noua parolă</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="input-group">
                            <label>Confirmă noua parolă</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn-glow"><i class='bx bx-refresh'></i> Resetează token</button>
                    </form>
                </div>
                <div class="glass-card">
                    <h3><i class='bx bx-bell-ring'></i> Preferințe psihedelice</h3>
                    <div class="pref-check">
                        <input type="checkbox" checked id="notif">
                        <label for="notif">Notificări holografice (email)</label>
                    </div>
                    <div class="pref-check">
                        <input type="checkbox" checked id="news">
                        <label for="news">Newsletter săptămânal cyber</label>
                    </div>
                    <button class="btn-glow" onclick="alert('Preferințe salvate în eter');"><i class='bx bx-check-double'></i> Salvează în matrix</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const tabs = document.querySelectorAll('.tab-neo');
    const panes = document.querySelectorAll('.pane');
    const navButtons = document.querySelectorAll('[data-nav]');

    function activateTab(tabId) {
        tabs.forEach(t => t.classList.remove('active'));
        panes.forEach(p => p.classList.remove('active-pane'));
        const activeTab = Array.from(tabs).find(t => t.getAttribute('data-tab') === tabId);
        if(activeTab) activeTab.classList.add('active');
        const activePane = document.getElementById(tabId);
        if(activePane) activePane.classList.add('active-pane');
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.getAttribute('data-tab');
            activateTab(tabId);
        });
    });

    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-nav');
            if(target) activateTab(target);
        });
    });
</script>
</body>
</html>