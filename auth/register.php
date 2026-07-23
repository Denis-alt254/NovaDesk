<?php
// Enable error reporting during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Require your Database Singleton class
require_once __DIR__ . '/../config/db.php';

// 2. Obtain the PDO connection instance from the Singleton
$db = Database::getInstance();
$pdo = $db->getConnection();

$errors = [];
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize Inputs
    $username = trim($_POST['username'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // 2. Validate Inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    // 3. Enforce Strong Password Policy (8+ chars, Uppercase, Lowercase, Number, Special char)
    $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if (!preg_match($passwordPattern, $password)) {
        $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    }

    // 4. Save to Database if Validation Passes
    if (empty($errors)) {
        try {
            // Check for existing user using your Database class helper or raw PDO
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1");
            $stmt->execute(['email' => $email, 'username' => $username]);

            if ($stmt->fetch()) {
                $errors[] = "Username or Email is already registered.";
            } else {
                // Hash Password using bcrypt
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                // Insert into Database
                $insertStmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :password, NOW())");
                $insertStmt->execute([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $hashedPassword
                ]);

                $success = "Registration successful! You can now log in.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaDesk - Register</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; padding-top: 50px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; border: none; color: white; font-size: 16px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        ul { margin: 0; padding-left: 20px; }
    </style>
</head>
<body>

<div class="card">
    <h2>NovaDesk Registration</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <small style="color: #666;">Min 8 characters (uppercase, lowercase, number, special char)</small>
        </div>

        <button type="submit">Create Account</button>
    </form>
</div>

</body>
</html>