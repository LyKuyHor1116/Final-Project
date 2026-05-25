<?php

require_once __DIR__ . '/config.php';

$pdo = getDB();

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (
        empty($name) ||
        empty($email) ||
        empty($password)
    ) {

        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {

        $error = 'Passwords do not match.';
    } else {

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $error = 'Email already exists.';
        } else {

            $hashed = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $email,
                $hashed
            ]);

            $_SESSION['user'] = [
                'id' => $pdo->lastInsertId(),
                'name' => $name,
                'email' => $email,
            ];

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <h1>Register</h1>

    <?php if ($error): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <input
            type="text"
            name="name"
            placeholder="Full Name"
            required>

        <br><br>

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

        <input
            type="password"
            name="confirm_password"
            placeholder="Confirm Password"
            required>

        <br><br>

        <button type="submit">
            Register
        </button>

    </form>

    <br>

    <a href="index.php">
        Login Here
    </a>

</body>

</html>