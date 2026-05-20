<?php
require_once 'config.php';
require_once 'language_switcher.php';

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';

// Ștergem doar erorile, nu toată sesiunea
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

function showError($error) {
    return !empty($error) ? "<p class=\"error-message\">" . htmlspecialchars($error) . "</p>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(lang('site_title')) ?> | <?= htmlspecialchars(lang('login')) ?></title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="login.css">
    <link rel="apple-touch-icon" sizes="180x180" href="images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon/favicon-16x16.png">
    <link rel="manifest" href="images/favicon/site.webmanifest">
</head>
<body>
    <div class="container">
        
        <div class="form-box login <?= isActiveForm('login', $activeForm); ?>">
            <form action="user_page.php" method="post">
                <h1><?= htmlspecialchars(lang('login')) ?></h1>
                <?= showError($errors['login']); ?>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="bx bxs-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Parolă" required>
                    <i class="bx bxs-lock-alt"></i>
                </div>
                <div class="forgot-link">
                    <a href="#"><?= htmlspecialchars(lang('forgot_password')) ?></a>
                </div>
                <button type="submit" name="login" class="btn"><?= htmlspecialchars(lang('login')) ?></button>
                <p><?= htmlspecialchars(lang('or_login_with_social') ?? 'sau autentifică-te cu') ?></p>
                <div class="social-icons">
                    <a href="#"><i class="bx bxl-google"></i></a>
                    <a href="#"><i class="bx bxl-facebook"></i></a>
                    <a href="#"><i class="bx bxl-github"></i></a>
                    <a href="#"><i class="bx bxl-linkedin"></i></a>
                </div>
            </form>
        </div>

        <div class="form-box register <?= isActiveForm('register', $activeForm); ?>">
            <form action="user_page.php" method="post">
                <h1><?= htmlspecialchars(lang('register')) ?></h1>
                <?= showError($errors['register']); ?>
                <div class="input-box">
                    <input type="text" name="name" placeholder="Nume" required>
                    <i class="bx bxs-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="bx bxs-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Parolă" required>
                    <i class="bx bxs-lock-alt"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="confirm_password" placeholder="Confirmă Parolă" required>
                    <i class="bx bxs-lock-alt"></i>
                </div>
                <button type="submit" name="register" class="btn"><?= htmlspecialchars(lang('register')) ?></button>
                <p><?= htmlspecialchars(lang('or_register_with_social') ?? 'sau înregistrează-te cu') ?></p>
                <div class="social-icons">
                    <a href="#"><i class="bx bxl-google"></i></a>
                    <a href="#"><i class="bx bxl-facebook"></i></a>
                    <a href="#"><i class="bx bxl-github"></i></a>
                    <a href="#"><i class="bx bxl-linkedin"></i></a>
                </div>
            </form>
        </div>

        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1><?= htmlspecialchars(lang('hello_welcome')) ?></h1>
                <p><?= htmlspecialchars(lang('new_here')) ?></p>
                <button class="btn register-btn"><?= htmlspecialchars(lang('create_account')) ?></button>
            </div>
            <div class="toggle-panel toggle-right">
                <h1><?= htmlspecialchars(lang('welcome_back')) ?></h1>
                <p><?= htmlspecialchars(lang('have_account')) ?></p>
                <button class="btn login-btn"><?= htmlspecialchars(lang('login')) ?></button>
            </div>
        </div>
    </div>

    <script src="login.js"></script>
</body>
</html>