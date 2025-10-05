<?php
session_start();
require_once "../includes/auth.php"; // reuse your authentication file

// Ensure only admins can access this page
$user = $_SESSION['user'] ?? null;
if (!$user || $user['role'] !== 'admin') {
    header("Location: ../index.php?page=login");
    exit;
}

// Determine current admin page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$validPages = ['dashboard', 'venues', 'users', 'profile', 'logout'];
if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}

// Function to render page content
function renderAdminPage($page)
{
    switch ($page) {
        case 'dashboard':
            include "dashboard.php";
            break;
        case 'venues':
            include "venues.php";
            break;
        case 'users':
            include "users.php";
            break;
        case 'profile':
            include "profile.php";
            break;
        case 'logout':
            include "logout.php";
            break;
        default:
            include "dashboard.php";
    }
}
?>

<?php include "../includes/admin/header.php"; ?>

<main class="admin-main">

    <div class="admin-content p-4">
        <?php renderAdminPage($page); ?>
    </div>
</main>

<?php include "../includes/admin/footer.php"; ?>