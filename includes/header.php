<?php
// includes/header.php

// Require database connection and authentication middleware
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Enforce login requirement for all pages including this header
require_login();

// Get the current script name to determine the active navigation link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget Analysis</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom Shared CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Sidebar Navigation -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-wallet2"></i> Smart Budget</h4>
        </div>
        <div class="nav flex-column mt-3">
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <a href="transactions.php" class="nav-link <?= $current_page == 'transactions.php' ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right"></i> Transaksi
            </a>
            <a href="categories.php" class="nav-link <?= $current_page == 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i> Kategori
            </a>
            <a href="budgets.php" class="nav-link <?= $current_page == 'budgets.php' ? 'active' : '' ?>">
                <i class="bi bi-pie-chart-fill"></i> Budget Bulanan
            </a>
            <a href="profile.php" class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <i class="bi bi-person-fill"></i> Profil
            </a>
            <a href="logout.php" class="nav-link text-danger mt-auto border-top">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="main-content">
        
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <!-- Sidebar Toggle Button (Mobile Only) -->
            <button class="btn btn-light d-lg-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            
            <!-- User Profile Dropdown -->
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-light border-0 dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-2" type="button" data-bs-toggle="dropdown">
                        <?php $display_name = $_SESSION['user_name'] ?? 'User'; ?>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($display_name) ?>&background=4f46e5&color=fff" alt="Profile" class="rounded-circle" width="32">
                        <span class="fw-medium d-none d-sm-inline"><?= htmlspecialchars($display_name) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Dynamic Content Starts Here (Injected by individual pages) -->
