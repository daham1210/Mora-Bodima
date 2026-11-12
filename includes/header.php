<?php require_once __DIR__ . '/../config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoraBodima - Boarding House Rating System</title>
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= $base_url ?>/index.php" class="logo">🏠 MoraBodima</a>
        <div class="nav-links">
            <a href="<?= $base_url ?>/index.php">Home</a>
            <a href="<?= $base_url ?>/search/index.php">Search & Filter</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'owner' || $_SESSION['role'] === 'admin'): ?>
                    <a href="<?= $base_url ?>/listings/add_house.php">+ Add Boarding</a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?= $base_url ?>/admin/dashboard.php">Admin Panel</a>
                <?php endif; ?>

                <span class="user-pill">
                    👤 <?= htmlspecialchars($_SESSION['full_name']) ?> (<?= ucfirst($_SESSION['role']) ?>)
                </span>
                <a href="<?= $base_url ?>/auth/logout.php" class="btn-logout">Logout</a>
            <?php else: ?>
                <a href="<?= $base_url ?>/auth/login.php" class="btn-nav">Login</a>
                <a href="<?= $base_url ?>/auth/register.php" class="btn-nav btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="main-container">