<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/includes/language_switcher.php';

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$success = $_SESSION['register_success'] ?? '';
$activeForm = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['register_success'], $_SESSION['active_form']);

function showError($error)
{
    return !empty($error) ? "<p class=\"error-message\">" . htmlspecialchars($error) . "</p>" : '';
}

function isActiveForm($formName, $activeForm)
{
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
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/images/favicon/site.webmanifest">
</head>

<body>
    <div class="container <?= $activeForm === 'register' ? 'active' : '' ?>">

        <div class="form-box login <?= isActiveForm('login', $activeForm); ?>">
            <form action="user_page.php" method="post">
                <h1><?= htmlspecialchars(lang('login')) ?></h1>
                <?php if ($success): ?>
                    <p class="success-message"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>
                <?= showError($errors['login']); ?>
                <div class="input-box">
                    <input type="email" name="email" placeholder="email" required>
                    <i class="bx bxs-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="parola" required>
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
                    <input type="text" name="name" placeholder="nume" required>
                    <i class="bx bxs-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="email" required>
                    <i class="bx bxs-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="parolă" required>
                    <i class="bx bxs-lock-alt"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="confirm_password" placeholder="confirmă parola" required>
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

    <script src="assets/js/login.js"></script>
</body>

</html>
