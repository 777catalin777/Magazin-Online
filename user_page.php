<?php 
require_once 'config.php';
require_once 'language_switcher.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = "Email și parola sunt obligatorii!";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['username'];
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
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                    $stmt->execute([$name, $email, $hashed_password]);
                    
                    $_SESSION['register_success'] = "Cont creat cu succes! Te poți autentifica.";
                }
            } catch (PDOException $e) {
                $_SESSION['register_error'] = "Eroare la înregistrare: " . $e->getMessage();
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
                $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ?, address = ? WHERE email = ?");
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
    <title><?= htmlspecialchars(lang('site_title') ?? 'Maison Lure') ?> | Profil Utilizator</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
    
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .profile-sidebar {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            border-radius: 12px;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            background: #fff;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .profile-main {
            padding: 1rem;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .btn {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            padding: 0.8rem 1.8rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(110, 142, 251, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .nav-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
                margin: 1rem;
                padding: 1rem;
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
            <p style="margin-top: 1rem; opacity: 0.9; font-size: 0.95rem;">
                Rol: <?= htmlspecialchars($_SESSION['role'] ?? 'user') ?>
            </p>
            
            <button onclick="window.location.href='logout.php'" class="btn btn-danger" style="margin-top: 2rem; background: rgba(220,53,69,0.8); width: 100%;">
                <?= htmlspecialchars(lang('logout') ?? 'Deconectare') ?>
            </button>
            <button onclick="window.location.href='index.php'" class="btn" style="margin-top: 1rem; width: 100%;">
                ← Înapoi la magazin
            </button>
        </div>

        <div class="profile-main">
            <h1 style="margin-bottom: 1.5rem;">Profilul Meu</h1>
            
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
                            <input type="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" readonly disabled style="background: #e9ecef;">
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
                    <p style="color: #666; font-style: italic;">Momentan nu aveți comenzi.</p>
                    <p style="margin-top: 1rem;">
                        <strong>0</strong> comenzi totale • Total cheltuit: <strong>0 lei</strong>
                    </p>
                    <button onclick="window.location.href='index.php'" class="btn" style="margin-top: 1rem;">
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
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>Parola nouă</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirmă parola nouă</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn">Schimbă Parola</button>
                    </form>
                    
                    <h3 class="section-title" style="margin-top: 2rem;">Preferințe</h3>
                    <label>
                        <input type="checkbox" checked> Primește notificări prin email
                    </label><br><br>
                    <label>
                        <input type="checkbox" checked> Newsletter cu noutăți și promoții
                    </label>
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
        
        document.documentElement.style.setProperty('--primary-color', '#428ed6');
    </script>
</body>
</html>