<?php
require_once __DIR__ . '/functions.php';
requireLogin();
$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = getUserRole();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Pharmacy Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>/pages/dashboard.php">
            <i class="fas fa-pills me-2"></i>Pharmacy Pro
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/dashboard.php">
                        <i class="fas fa-chart-line me-1"></i>Dashboard
                    </a>
                </li>
                <?php if ($userRole === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'medicines.php' || $currentPage === 'medicine_form.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/medicines.php">
                            <i class="fas fa-capsules me-1"></i>Medicines
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'suppliers.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/suppliers.php">
                            <i class="fas fa-truck me-1"></i>Suppliers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/users.php">
                            <i class="fas fa-users-cog me-1"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'sales.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/pages/sales.php">
                        <i class="fas fa-shopping-cart me-1"></i>POS
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['admin_name'] ?? 'User') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><span class="dropdown-item-text small"><strong><?= ucfirst($userRole) ?></strong></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/pages/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container-fluid my-4">
