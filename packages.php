<?php

// Force PHP to display hidden errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/security.php'; // Includes e() and e_attr() for output encoding

$packages = [];
$error = '';

try {
    // Fetch active service packages ordered by price
    $stmt = Database::getInstance()->runQuery(
        "SELECT id, title, category, description, estimated_timeline, starting_price 
         FROM service_packages 
         WHERE is_active = 1 
         ORDER BY starting_price ASC"
    );
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Unable to load service packages at this time. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaDesk - Service Packages</title>
    <link rel="stylesheet" href="assets/css/packages.css">
</head>
<body>

<section class="pricing-section">
    <div class="pricing-header">
        <h2>Agency Service Packages</h2>
        <p>Explore our tailored solutions, estimated delivery timelines, and transparent pricing.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= e($error) ?></div>
    <?php else: ?>
        <div class="pricing-grid">
            <?php foreach ($packages as $package): ?>
                <div class="package-card">
                    <!-- Category Tag -->
                    <div class="category-badge"><?= e($package['category']) ?></div>

                    <!-- Package Title -->
                    <h3 class="package-title"><?= e($package['title']) ?></h3>

                    <!-- Pricing -->
                    <div class="package-price">
                        <span class="currency">$</span>
                        <span class="amount"><?= e(number_format($package['starting_price'], 2)) ?></span>
                        <span class="cycle">starting price</span>
                    </div>

                    <!-- Delivery Timeline -->
                    <div class="package-timeline">
                        <strong>⏱ Delivery Timeline:</strong> <?= e($package['estimated_timeline']) ?>
                    </div>

                    <!-- Description -->
                    <div class="package-description">
                        <?= e($package['description']) ?>
                    </div>

                    <!-- Select Package Button -->
                    <a href="auth/register.php?package_id=<?= e_attr($package['id']) ?>" class="btn-package">
                        Get Started
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

</body>
</html>