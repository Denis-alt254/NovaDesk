<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Authenticate User
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: auth/login.php');
    exit();
}

$db = Database::getInstance();
$userId = (int)$_SESSION['user_id'];
$requestId = (int)($_GET['id'] ?? 0);
$request = null;
$error = '';

// Validate Request ID parameter
if ($requestId <= 0) {
    $error = "Invalid request reference.";
} else {
    try {
        /* ------------------------------------------------------------------
         * IDOR PROTECTION:
         * We bind BOTH `cr.id = :request_id` AND `cr.user_id = :user_id`.
         * If the user attempts to view another client's request ID in the URL,
         * the database query returns NULL, completely blocking unauthorized access.
         * ------------------------------------------------------------------ */
        $stmt = $db->runQuery(
            "SELECT 
                cr.id, 
                cr.client_name, 
                cr.client_email, 
                cr.subject, 
                cr.service_type, 
                cr.message, 
                cr.status, 
                cr.submitted_at, 
                cr.updated_at,
                sp.title AS package_title,
                sp.starting_price,
                sp.estimated_timeline
             FROM consultation_requests cr
             LEFT JOIN service_packages sp ON cr.package_id = sp.id
             WHERE cr.id = :request_id AND cr.user_id = :user_id
             LIMIT 1",
            [
                'request_id' => $requestId,
                'user_id'    => $userId
            ]
        );

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            // Generic error message to prevent resource enumeration
            $error = "The requested consultation brief was not found or you do not have permission to view it.";
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = "A system error occurred while loading the request details.";
    }
}

// Helper to determine status badge classes
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details #<?= e(str_pad($requestId, 5, '0', STR_PAD_LEFT)) ?> - NovaDesk</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/view-request.css">
</head>
<body>

<div class="details-container">

    <div class="details-nav">
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error">
            <p><strong>Access Denied:</strong> <?= e($error) ?></p>
        </div>
    <?php elseif ($request): ?>

        <div class="details-card">
            <!-- Header Section -->
            <div class="details-header">
                <div>
                    <span class="ref-badge">Request #<?= e(str_pad($request['id'], 5, '0', STR_PAD_LEFT)) ?></span>
                    <h2><?= e($request['subject']) ?></h2>
                </div>
                <div>
                    <span class="badge <?= e(getStatusBadgeClass($request['status'])) ?>">
                        <?= e($request['status']) ?>
                    </span>
                </div>
            </div>

            <!-- Meta Information Grid -->
            <div class="details-grid">
                <div class="grid-item">
                    <label>Category / Service Type</label>
                    <span><?= e($request['service_type']) ?></span>
                </div>

                <div class="grid-item">
                    <label>Selected Package</label>
                    <span><?= e($request['package_title'] ?? 'Custom Request (No Package)') ?></span>
                </div>

                <div class="grid-item">
                    <label>Submitted On</label>
                    <span><?= e(date('F j, Y \a\t g:i A', strtotime($request['submitted_at']))) ?></span>
                </div>

                <div class="grid-item">
                    <label>Last Status Update</label>
                    <span><?= e(date('F j, Y \a\t g:i A', strtotime($request['updated_at']))) ?></span>
                </div>
            </div>

            <?php if (!empty($request['package_title'])): ?>
                <!-- Package Details Summary (if tied to a package) -->
                <div class="package-summary-box">
                    <h4>Package Reference Info</h4>
                    <p><strong>Starting Price:</strong> $<?= e(number_format($request['starting_price'], 2)) ?></p>
                    <p><strong>Estimated Timeline:</strong> <?= e($request['estimated_timeline']) ?></p>
                </div>
            <?php endif; ?>

            <!-- Contact Snapshot -->
            <div class="client-snapshot">
                <label>Submitted Contact Details</label>
                <p><strong>Name:</strong> <?= e($request['client_name']) ?> | <strong>Email:</strong> <?= e($request['client_email']) ?></p>
            </div>

            <!-- Project Brief / Message -->
            <div class="message-box">
                <h3>Project Brief & Specifications</h3>
                <div class="message-content">
                    <?= nl2br(e($request['message'])) ?>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>