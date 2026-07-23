<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

// 1. Enforce Authentication
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Verify CSRF Token
    if (!verify_csrf_token()) {
        header('Location: dashboard.php?error=invalid_csrf');
        exit();
    }

    $validator = validateInput();
    $requestId = (int)$validator->get('request_id', 0);
    $userId    = $_SESSION['user_id'];

    if ($requestId > 0) {
        try {
            $db = Database::getInstance();

            // 3. Ensure the request exists, belongs to the logged-in user, AND is currently 'Pending'
            $checkStmt = $db->runQuery(
                "SELECT id FROM consultation_requests WHERE id = :id AND user_id = :user_id AND status = 'Pending' LIMIT 1",
                ['id' => $requestId, 'user_id' => $userId]
            );

            if ($checkStmt->fetch()) {
                // Update status to 'Cancelled' (or DELETE the record based on your business logic)
                $db->runQuery(
                    "UPDATE consultation_requests SET status = 'Cancelled' WHERE id = :id AND user_id = :user_id",
                    ['id' => $requestId, 'user_id' => $userId]
                );

                header('Location: dashboard.php?request_cancelled=1');
                exit();
            } else {
                header('Location: dashboard.php?error=request_not_found');
                exit();
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            header('Location: dashboard.php?error=system');
            exit();
        }
    }
}

header('Location: dashboard.php');
exit();