<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php'; // Includes e() for output escaping

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: auth/login.php');
    exit();
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$user = null;
$error = '';

try {
    // Fetch full client account details from the database
    $stmt = $db->runQuery(
        "SELECT id, username, email, company_name, industry, created_at, updated_at 
         FROM users 
         WHERE id = :id LIMIT 1",
        ['id' => $userId]
    );
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Client profile not found.";
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "A system error occurred while retrieving your profile details.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile - NovaDesk</title>
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>

<div class="profile-container">

    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; color: #ef4444; padding: 15px; border-radius: 8px; border: 1px solid #fecaca; text-align: center;">
            <?= e($error) ?>
        </div>
    <?php elseif ($user): ?>

        <div class="profile-card">
            <!-- Header Banner & Avatar -->
            <div class="profile-banner"></div>
            <div class="profile-header-content">
                <div class="avatar-circle">
                    <?= e(strtoupper(substr($user['username'], 0, 1))) ?>
                </div>
                <div class="user-title">
                    <h2><?= e($user['username']) ?></h2>
                    <p><?= e($user['company_name'] ?? 'Independent Client') ?> • <?= e($user['industry'] ?? 'General') ?></p>
                </div>
            </div>

            <div class="profile-body">
                <!-- Contact & Account Details -->
                <h3 class="section-title">Account Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Username</label>
                        <span><?= e($user['username']) ?></span>
                    </div>

                    <div class="info-item">
                        <label>Email Address</label>
                        <span><?= e($user['email']) ?></span>
                    </div>

                    <div class="info-item">
                        <label>Client ID</label>
                        <span>#<?= e(str_pad($user['id'], 5, '0', STR_PAD_LEFT)) ?></span>
                    </div>
                </div>

                <!-- Organization & Industry -->
                <h3 class="section-title">Organization Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Company / Agency</label>
                        <span><?= e($user['company_name'] ?: 'Independent Client') ?></span>
                    </div>

                    <div class="info-item">
                        <label>Industry</label>
                        <span><?= e($user['industry'] ?: 'General') ?></span>
                    </div>
                </div>

                <!-- Registration & Account Activity -->
                <h3 class="section-title">Registration History</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Member Since</label>
                        <span><?= e(date('F j, Y, g:i a', strtotime($user['created_at']))) ?></span>
                    </div>

                    <div class="info-item">
                        <label>Last Profile Update</label>
                        <span><?= e(date('F j, Y, g:i a', strtotime($user['updated_at']))) ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="profile-actions">
                    <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>