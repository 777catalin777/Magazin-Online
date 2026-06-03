<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/user_page.php';
    exit;
}

header('Location: login.php');
exit;
?>
