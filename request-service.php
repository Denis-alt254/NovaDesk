<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

// 1. Enforce Authentication Middleware
require_auth();

$db = Database::getInstance();
$errors = [];

// 2. Fetch logged-in user details to pre-fill name & email
$userId = $_SESSION['user_id'];
$userStmt = $db->runQuery("SELECT username, email FROM users WHERE id = :id LIMIT 1", ['id' => $userId]);
$currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);

// 3. Fetch active service packages for selection dropdown
try {
    $pkgStmt = $db->runQuery("SELECT id, title, category, starting_price FROM service_packages WHERE is_active = 1 ORDER BY starting_price ASC");
    $packages = $pkgStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $packages = [];
}

// Capture package_id if pre-selected via query string
$selectedPackageId = (int)($_GET['package_id'] ?? $_POST['package_id'] ?? 0);

// 4. Handle POST Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Check
    if (!verify_csrf_token()) {
        $errors[] = "Invalid or expired session token. Please reload and try again.";
    } else {
        // Run Centralized Input Validator
        $validator = validateInput();

        $validator->required([
            'client_name'  => 'Client name',
            'client_email' => 'Email address',
            'subject'      => 'Subject',
            'service_type' => 'Service category',
            'message'      => 'Project scope details'
        ])
        ->maxLength('client_name', 100)
        ->email('client_email')
        ->maxLength('client_email', 150)
        ->minLength('subject', 3)
        ->maxLength('subject', 150)
        ->minLength('message', 15);

        if ($validator->isValid()) {
            $clientName  = $validator->get('client_name');
            $clientEmail = $validator->get('client_email');
            $subject     = $validator->get('subject');
            $serviceType = $validator->get('service_type');
            $message     = $validator->get('message');

            // Optional Package Validation
            $packageId = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
            if (!is_null($packageId) && $packageId > 0) {
                $checkPkg = $db->runQuery("SELECT id FROM service_packages WHERE id = :id AND is_active = 1 LIMIT 1", ['id' => $packageId]);
                if (!$checkPkg->fetch()) {
                    $packageId = null;
                }
            } else {
                $packageId = null;
            }

            // Insert into consultation_requests table
            try {
                $db->runQuery(
                    "INSERT INTO consultation_requests (user_id, package_id, client_name, client_email, subject, service_type, message, status, submitted_at) 
                     VALUES (:user_id, :package_id, :client_name, :client_email, :subject, :service_type, :message, 'Pending', NOW())",
                    [
                        'user_id'      => $userId,
                        'package_id'   => $packageId,
                        'client_name'  => $clientName,
                        'client_email' => $clientEmail,
                        'subject'      => $subject,
                        'service_type' => $serviceType,
                        'message'      => $message
                    ]
                );

                // Redirect on success
                header('Location: dashboard.php?request_submitted=1');
                exit();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors[] = "A system error occurred while submitting your consultation request.";
            }
        } else {
            $errors = $validator->getErrors();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Consultation - NovaDesk</title>
    <link rel="stylesheet" href="assets/css/request.css">
    <style>
        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-cancel {
            display: inline-block;
            padding: 12px 20px;
            background-color: #f3f4f6;
            color: #4b5563;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .btn-cancel:hover {
            background-color: #e5e7eb;
            color: #1f2937;
        }
    </style>
</head>
<body>

<div class="request-container">
    <div class="request-header">
        <h2>Submit Consultation Request</h2>
        <p>Send your project requirements to our team for review and quotation.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="requestForm" action="request-service.php" method="POST" novalidate>
        <!-- CSRF Token Hidden Field -->
        <input type="hidden" name="csrf_token" value="<?= e_attr($_SESSION['session_token'] ?? '') ?>">

        <!-- Service Package (Optional / Pre-selectable) -->
        <div class="form-group">
            <label for="package_id">Service Package (Optional)</label>
            <select name="package_id" id="package_id">
                <option value="">-- Custom / No Specific Package --</option>
                <?php foreach ($packages as $pkg): ?>
                    <option value="<?= e_attr($pkg['id']) ?>" <?= ($selectedPackageId === (int)$pkg['id']) ? 'selected' : '' ?>>
                        <?= e($pkg['title']) ?> (<?= e($pkg['category']) ?> - From $<?= e(number_format($pkg['starting_price'], 2)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Name and Email -->
        <div class="form-row">
            <div class="form-group">
                <label for="client_name">Your Name *</label>
                <input type="text" id="client_name" name="client_name" 
                       value="<?= e_attr($_POST['client_name'] ?? $currentUser['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="client_email">Email Address *</label>
                <input type="email" id="client_email" name="client_email" 
                       value="<?= e_attr($_POST['client_email'] ?? $currentUser['email'] ?? '') ?>" required>
            </div>
        </div>

        <!-- Subject and Service Type -->
        <div class="form-row">
            <div class="form-group">
                <label for="subject">Request Subject *</label>
                <input type="text" id="subject" name="subject" 
                       value="<?= e_attr($_POST['subject'] ?? '') ?>" 
                       placeholder="e.g., E-Commerce Platform Build" required>
            </div>

            <div class="form-group">
                <label for="service_type">Service Category *</label>
                <select name="service_type" id="service_type" required>
                    <option value="">-- Select Category --</option>
                    <option value="Engineering" <?= (($_POST['service_type'] ?? '') === 'Engineering') ? 'selected' : '' ?>>Engineering</option>
                    <option value="Design" <?= (($_POST['service_type'] ?? '') === 'Design') ? 'selected' : '' ?>>Design</option>
                    <option value="Security" <?= (($_POST['service_type'] ?? '') === 'Security') ? 'selected' : '' ?>>Security</option>
                    <option value="Consulting" <?= (($_POST['service_type'] ?? '') === 'Consulting') ? 'selected' : '' ?>>Consulting</option>
                </select>
            </div>
        </div>

        <!-- Message -->
        <div class="form-group">
            <label for="message">Project Scope & Details *</label>
            <textarea id="message" name="message" rows="6" 
                      placeholder="Outline your project scope, features required, target timeline, or technical details..." required><?= e($_POST['message'] ?? '') ?></textarea>
        </div>

        <!-- Action Buttons -->
        <div class="form-actions">
            <button type="submit" class="btn-submit">Submit Consultation Request</button>
            <a href="dashboard.php" class="btn-cancel" id="cancelBtn">Cancel</a>
        </div>
    </form>
</div>

<script>
    // Prompt confirmation if user has started filling out subject or message before cancelling
    document.getElementById('cancelBtn').addEventListener('click', function (e) {
        const subject = document.getElementById('subject').value.trim();
        const message = document.getElementById('message').value.trim();

        if (subject !== '' || message !== '') {
            if (!confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
                e.preventDefault();
            }
        }
    });
</script>

</body>
</html>