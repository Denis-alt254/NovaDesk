<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php'; // Includes e() for output escaping

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Authenticate Client
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: auth/login.php');
    exit();
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$requests = [];
$error = '';

// Check for successful submission redirect
$showSuccessMessage = isset($_GET['request_submitted']) && $_GET['request_submitted'] == 1;

try {
    // 2. Fetch all consultation requests for the logged-in client (joining package details if applicable)
    $stmt = $db->runQuery(
        "SELECT 
            cr.id, 
            cr.subject, 
            cr.service_type, 
            cr.message, 
            cr.status, 
            cr.submitted_at, 
            sp.title AS package_title
         FROM consultation_requests cr
         LEFT JOIN service_packages sp ON cr.package_id = sp.id
         WHERE cr.user_id = :user_id
         ORDER BY cr.submitted_at DESC",
        ['user_id' => $userId]
    );
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Unable to retrieve your consultation requests at this time.";
}

// Helper to map status to CSS badge class
function getStatusBadgeClass(string $status): string {
    switch ($status) {
        case 'In Review':  return 'badge-in-review';
        case 'Quoted':     return 'badge-quoted';
        case 'Completed':  return 'badge-completed';
        case 'Cancelled':  return 'badge-cancelled';
        case 'Pending':
        default:           return 'badge-pending';
    }
}

// Metric counters
$totalRequests = count($requests);
$pendingCount  = count(array_filter($requests, fn($r) => $r['status'] === 'Pending'));
$activeCount   = count(array_filter($requests, fn($r) => in_array($r['status'], ['In Review', 'Quoted'])));
$completedCount = count(array_filter($requests, fn($r) => $r['status'] === 'Completed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - NovaDesk</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

<div class="dashboard-container">

    <!-- Header Navigation -->
    <header class="dashboard-header">
        <div>
            <h1>Client Portal</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Welcome back, <strong><?= e($_SESSION['username'] ?? 'Client') ?></strong></p>
        </div>
        <div class="header-actions">
            <a href="request-service.php" class="btn btn-primary">+ New Request</a>
            <a href="profile.php" class="btn btn-outline">Profile</a>
            <a href="auth/logout.php" class="btn btn-outline" style="color: #ef4444;">Logout</a>
        </div>
    </header>

    <?php if ($showSuccessMessage): ?>
        <div class="alert-success">
            ✓ Your consultation request has been successfully submitted! Our team will review it shortly.
        </div>
    <?php endif; ?>

    <!-- Summary Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <h3>Total Requests</h3>
            <div class="number"><?= e($totalRequests) ?></div>
        </div>
        <div class="metric-card">
            <h3>Pending Review</h3>
            <div class="number" style="color: #d97706;"><?= e($pendingCount) ?></div>
        </div>
        <div class="metric-card">
            <h3>In Progress / Quoted</h3>
            <div class="number" style="color: #4f46e5;"><?= e($activeCount) ?></div>
        </div>
        <div class="metric-card">
            <h3>Completed</h3>
            <div class="number" style="color: #16a34a;"><?= e($completedCount) ?></div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="requests-card">
        <h2>Your Consultation Requests</h2>

        <?php if (!empty($error)): ?>
            <p style="color: #ef4444;"><?= e($error) ?></p>
        <?php elseif (empty($requests)): ?>
            <div class="empty-state">
                <p>You haven't submitted any consultation requests yet.</p>
                <br>
                <a href="request-service.php" class="btn btn-primary">Submit Your First Request</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Selected Package</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><strong>#<?= e(str_pad($req['id'], 5, '0', STR_PAD_LEFT)) ?></strong></td>
                                <td><?= e($req['subject']) ?></td>
                                <td><?= e($req['service_type']) ?></td>
                                <td><?= e($req['package_title'] ?? 'Custom Request') ?></td>
                                <td><?= e(date('M j, Y', strtotime($req['submitted_at']))) ?></td>
                                <td>
                                    <span class="badge <?= e(getStatusBadgeClass($req['status'])) ?>">
                                        <?= e($req['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>