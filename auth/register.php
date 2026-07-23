<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } else {
        try {
            $db = Database::getInstance();
            
            // Check for duplicate username or email
            $checkStmt = $db->runQuery(
                "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1",
                ['username' => $username, 'email' => $email]
            );

            if ($checkStmt->fetch()) {
                $error = "Username or Email is already taken.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                $db->runQuery(
                    "INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :password, NOW())",
                    ['username' => $username, 'email' => $email, 'password' => $hashedPassword]
                );

                // Fetch new user ID
                $newUserId = $db->getConnection()->lastInsertId();

                // Auto-login user and establish secure session
                regenerate_secure_session([
                    'user_id'  => $newUserId,
                    'username' => $username,
                    'email'    => $email
                ]);

                // Redirect directly to dashboard
                header('Location: ../dashboard.php');
                exit();
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
    <title>NovaDesk - Register</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <h2>Create an Account</h2>
        <p>Join NovaDesk to manage your dashboard</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="registerForm" action="register.php" method="POST" novalidate>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="e.g. johndoe" required>
            <div class="field-error" id="username_err">Username must be at least 3 characters.</div>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@example.com" required>
            <div class="field-error" id="email_err">Please enter a valid email address.</div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                <span class="toggle-eye" onclick="togglePasswordVisibility('password', this)">👁️</span>
            </div>
            <div class="field-error" id="password_err">Must be 8+ chars with upper, lower, number & special char.</div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" placeholder="••••••••" required>
                <span class="toggle-eye" onclick="togglePasswordVisibility('confirm_password', this)">👁️</span>
            </div>
            <div class="field-error" id="confirm_password_err">Passwords do not match.</div>
        </div>

        <button type="submit" class="btn-submit">Register & Continue</button>
    </form>

    <div class="toggle-container">
        Already have an account? <a href="login.php" class="toggle-btn">Sign In</a>
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

    document.getElementById('registerForm').addEventListener('submit', function (e) {
        let valid = true;

        document.querySelectorAll('.field-error').forEach(el => el.style.display = 'none');
        document.querySelectorAll('input').forEach(el => el.classList.remove('invalid'));

        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const pass = document.getElementById('password');
        const confirmPass = document.getElementById('confirm_password');

        if (username.value.trim().length < 3) {
            showFieldError(username, 'username_err');
            valid = false;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email.value.trim())) {
            showFieldError(email, 'email_err');
            valid = false;
        }

        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
        if (!passwordPattern.test(pass.value)) {
            showFieldError(pass, 'password_err');
            valid = false;
        }

        if (pass.value !== confirmPass.value || !confirmPass.value) {
            showFieldError(confirmPass, 'confirm_password_err');
            valid = false;
        }

        if (!valid) e.preventDefault();
    });

    function showFieldError(inputEl, errorId) {
        inputEl.classList.add('invalid');
        document.getElementById(errorId).style.display = 'block';
    }
</script>

</body>
</html>