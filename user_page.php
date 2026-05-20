<?php 
require_once 'config.php';
require_once 'language_switcher.php';

// Verificări de securitate
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'user') {
    header("Location: admin_page.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('site_title') ?? 'Maison Lure' ?> | Profil Utilizator</title>
    <link rel="stylesheet" href="styles.css">
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
                Membru din: <?= date('F Y') ?>
            </p>
            
            <button onclick="window.location.href='logout.php'" class="btn" style="margin-top: 2rem; background: rgba(255,255,255,0.2); width: 100%;">
                <?= lang('logout') ?? 'Deconectare' ?>
            </button>
        </div>

        <div class="profile-main">
            <h1 style="margin-bottom: 1.5rem;">Profilul Meu</h1>

            <div class="nav-tabs">
                <div class="tab active" onclick="showTab(0)">Informații Personale</div>
                <div class="tab" onclick="showTab(1)">Comenzi</div>
                <div class="tab" onclick="showTab(2)">Setări Cont</div>
            </div>

            <div id="tab0" class="tab-content">
                <div class="info-card">
                    <h3 class="section-title">Date Personale</h3>
                    <form id="profileForm">
                        <div class="form-group">
                            <label>Nume complet</label>
                            <input type="text" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" id="name">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Telefon</label>
                            <input type="tel" placeholder="Introduceți numărul de telefon" id="phone">
                        </div>
                        <div class="form-group">
                            <label>Adresă</label>
                            <input type="text" placeholder="Strada, număr, bloc..." id="address">
                        </div>
                        <button type="button" onclick="saveProfile()" class="btn">Salvează Modificările</button>
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
                </div>
            </div>

            <div id="tab2" class="tab-content" style="display: none;">
                <div class="info-card">
                    <h3 class="section-title">Securitate Cont</h3>
                    <button onclick="alert('Funcționalitate schimbare parolă în dezvoltare')" class="btn" style="margin-bottom: 1rem;">
                        Schimbă Parola
                    </button>
                    
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
                el.classList.toggle('active', i === n);
            });
        }
        
        function saveProfile() {
            const name = document.getElementById('name').value;
            alert('Profil actualizat cu succes! 👕\n\n' + name);
        }
        
        document.documentElement.style.setProperty('--primary-color', '#428ed6');
    </script>
</body>
</html>