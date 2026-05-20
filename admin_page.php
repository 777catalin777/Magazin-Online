<?php
session_start();
require_once 'config.php';
require_once 'language_switcher.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_page.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(lang('site_title')) ?> | Admin</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <div class="box">
        <h1><?= htmlspecialchars(lang('welcome')) ?>, <span><?= htmlspecialchars($_SESSION['name']); ?></span>!</h1>
        <p><?= htmlspecialchars(lang('admin_dashboard')) ?></p>
        <p style="margin: 10px 0;">Rol: Administrator</p>
        <div style="display: flex; gap: 15px; margin-top: 20px;">
            <button onclick="window.location.href='index.php'" class="btn">← Magazin</button>
            <button onclick="window.location.href='logout.php'" class="btn" style="background: linear-gradient(135deg, #dc3545, #c82333);"><?= htmlspecialchars(lang('logout')) ?></button>
        </div>
    </div>
</body>
</html>