<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = Database::getInstance()->runQuery(
                "SELECT id, username, email, password FROM users WHERE email = :email OR username = :username LIMIT 1",
                ['email' => $identifier, 'username' => $identifier]
            );
            $user = $stmt->fetch();

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
            $error = "A system error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaDesk - Login</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <h2>Welcome Back</h2>
        <p>Sign in to your NovaDesk account</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="loginForm" action="login.php" method="POST" novalidate>
        <div class="form-group">
            <label for="identifier">Username or Email</label>
            <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" placeholder="e.g. john or john@example.com" required>
            <div class="field-error" id="identifier_err">Please enter your username or email.</div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                <span class="toggle-eye" onclick="togglePasswordVisibility('password', this)">👁️</span>
            </div>
            <div class="field-error" id="password_err">Please enter your password.</div>
        </div>

        <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <div class="toggle-container">
        Don't have an account? <a href="register.php" class="toggle-btn">Create Account</a>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, eyeBtn) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            eyeBtn.textContent = '🙈';
        } else {
            input.type = 'password';
            eyeBtn.textContent = '👁️';
        }
    }

    document.getElementById('loginForm').addEventListener('submit', function (e) {
        let valid = true;
        
        document.querySelectorAll('.field-error').forEach(el => el.style.display = 'none');
        document.querySelectorAll('input').forEach(el => el.classList.remove('invalid'));

        const id = document.getElementById('identifier');
        const pass = document.getElementById('password');

        if (!id.value.trim()) {
            id.classList.add('invalid');
            document.getElementById('identifier_err').style.display = 'block';
            valid = false;
        }

        if (!pass.value) {
            pass.classList.add('invalid');
            document.getElementById('password_err').style.display = 'block';
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
</script>

</body>
</html>