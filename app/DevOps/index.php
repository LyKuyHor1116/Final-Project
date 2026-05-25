<?php

require_once __DIR__ . '/config.php';

$pdo = getDB();

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = 'Please fill in all fields.';
    } else {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ];

            header('Location: dashboard.php');
            exit;
        } else {

            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Brew & Bean</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>Login</h1>

    <?php if ($error): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <input
            type="email"
            name="email"
            placeholder="Email"
            required>

        <br><br>

        <input
            type="password"
            name="password"
            placeholder="Password"
            required>

        <br><br>

        <button type="submit">
            Login
        </button>

    </form>

    <br>

    <a href="register.php">
        Register Here
    </a>

</body>

</html>