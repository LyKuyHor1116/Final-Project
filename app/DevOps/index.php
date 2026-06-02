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

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>

<body class="auth-page">

    <div class="auth-wrapper">

        <div class="auth-card page-enter">

            <div class="auth-logo">
                <span class="logo-icon">☕</span>
                <h1 class="logo-text">Brew & Bean</h1>
            </div>

            <h2 class="auth-title">Welcome</h2>
            <p class="auth-subtitle">Sign in to your account</p>

            <?php if ($error): ?>
                <div class="message message-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" novalidate>

                <div class="form-group">
                    <label for="email">Email</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="your@email.com"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required>
                </div>

                <button type="submit" class="btn btn-primary">
                    Sign In
                </button>

            </form>

            <p class="auth-link">
                Don't have an account?
                <a href="register.php">Create one</a>
            </p>

        </div>

    </div>

</body>

</html>