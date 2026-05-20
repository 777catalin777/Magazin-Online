<?php

require_once 'config.php';

if (!isset($pdo)) {
    die("Eroare: Conexiunea la baza de date nu a fost stabilită.");
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    
    $name = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "Toate câmpurile sunt obligatorii!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresa de email nu este validă!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Parola trebuie să aibă minim 6 caractere!";
    } elseif ($password !== $confirm) {
        $errors[] = "Parolele nu se potrivesc!";
    }

    if (empty($errors)) {
        try {

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Există deja un cont cu acest email!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password]);
                
                $success = "Contul a fost creat cu succes! Te poți autentifica acum.";
            }
        } catch (PDOException $e) {
            $errors[] = "Eroare la înregistrare: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Email și parola sunt obligatorii!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Email sau parola incorectă!";
            }
        } catch (PDOException $e) {
            $errors[] = "Eroare la autentificare.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - <?= $site_name ?? 'Magazin' ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: #000; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
    </style>
</head>
<body>

<div class="container">
    <h2>Autentificare / Înregistrare</h2>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <h3>Login</h3>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Parolă" required>
        <button type="submit" name="login">Intră în cont</button>
    </form>

    <hr>

    <h3>Creare cont nou</h3>
    <form method="POST">
        <input type="text" name="name" placeholder="Nume complet" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Parolă" required>
        <input type="password" name="confirm_password" placeholder="Confirmă parola" required>
        <button type="submit" name="register">Înregistrează-te</button>
    </form>
</div>

</body>
</html>