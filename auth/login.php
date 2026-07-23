<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? ''); // Username or Email
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {

            // Get the PDO instance from your Database singleton
            $pdo = Database::getInstance()->getConnection();
            
            // Query using distinct parameter names for email and username
            $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE email = :email OR username = :username LIMIT 1");

            // Execute with matching key-value pairs
            $stmt->execute([
                'email'    => $identifier,
                'username' => $identifier
            ]);

            $user = $stmt->fetch();

            // Verify Bcrypt Hash
            if ($user && password_verify($password, $user['password'])) {
                regenerate_secure_session([
                    'user_id'  => $user['id'],
                    'username' => $user['username'],
                    'email'    => $user['email']
                ]);

                header('Location: ../dashboard.php');
                exit();
            } else {
                $error = "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NovaDesk - Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; padding-top: 50px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; border: none; color: white; font-size: 16px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h2>NovaDesk Login</h2>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="identifier">Username or Email</label>
            <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit">Sign In</button>
    </form>
</div>

</body>
</html>