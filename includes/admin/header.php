<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Resort Venue Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<header class="admin-header">
    <div class="admin-logo">
        <a href="index.php?page=dashboard">
            <img src="../assets/images/logo-white.png" alt="Admin Logo">
        </a>
    </div>
    <nav class="admin-nav">
        <ul class="nav-menu">
            <li><a href="index.php?page=dashboard" class="<?= ($_GET['page'] ?? '') === 'dashboard' ? 'active' : '' ?>"><i class="fa fa-home"></i> Dashboard</a></li>
            <li><a href="index.php?page=venues" class="<?= ($_GET['page'] ?? '') === 'venues' ? 'active' : '' ?>"><i class="fa fa-map-marker-alt"></i> Venues</a></li>
            <li><a href="index.php?page=users" class="<?= ($_GET['page'] ?? '') === 'users' ? 'active' : '' ?>"><i class="fa fa-users"></i> Users</a></li>
            <li><a href="index.php?page=profile" class="<?= ($_GET['page'] ?? '') === 'profile' ? 'active' : '' ?>"><i class="fa fa-user-circle"></i> Profile</a></li>
            <li><a href="#" onclick="logout()"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <div class="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </nav>
</header>
