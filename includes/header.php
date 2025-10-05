<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Include Font Awesome (CDN in your <head>) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="./assets/css/client.css" rel="stylesheet">
    <link href="./assets/css/global.css" rel="stylesheet">
</head>

<body>
    
    <!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold <?= $page === 'home' ? 'active' : '' ?>" href="index.php?page=home">
            <img src="./assets/images/logo.png" alt="Resort Rental Logo" height="50" class="d-inline-block align-text-top">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'home' ? 'active' : '' ?>" href="index.php?page=home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'venue' ? 'active' : '' ?>" href="index.php?page=venue">Venues</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'booking' ? 'active' : '' ?>" href="index.php?page=booking">Book Now</a>
                </li>
            </ul>

            <div class="navbar-nav">
                <div class="user-menu d-none" id="userMenu">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <span id="userName"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=profile"><i class="fas fa-user me-2"></i>Account</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
                <div class="auth-buttons" id="authButtons">
                    <a href="index.php?page=login" class="btn btn-outline-primary me-2 <?= $page === 'login' ? 'active' : '' ?>">Login</a>
                    <a href="index.php?page=register" class="btn btn-primary <?= $page === 'register' ? 'active' : '' ?>">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
</nav>
