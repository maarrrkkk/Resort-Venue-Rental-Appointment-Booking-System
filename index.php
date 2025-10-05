<?php
session_start();

require_once "includes/auth.php";

// Determine current page (default = home)
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$validPages = ['home', 'venue', 'booking', 'about', 'contact', 'login', 'register', 'dashboard', 'profile', 'resetPassword'];
if (!in_array($page, $validPages)) {
    $page = 'home';
}

// User info (mock auth context from session)
$user = $_SESSION['user'] ?? null;
$isAuthenticated = isset($user);

// Simple navigation (React's switch-case equivalent)
function renderPage($page, $user, $isAuthenticated)
{
    switch ($page) {
        case 'home':
            include "pages/home.php";
            break;
        case 'venue':
            include "pages/venue.php";
            break;
        case 'login':
            include "pages/login.php";
            break;
        case 'register':
            include "pages/register.php";
            break;
        case 'resetPassword':
            include "pages/resetPassword.php";
            break;
        case 'booking':
            include "pages/booking.php";
            break;

        case 'dashboard':
            if (!$isAuthenticated) {
                header("Location: index.php?page=login");
                exit;
            }
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php?page=dashboard");
                exit;
            } else {
                include "pages/dashboard_user.php";
            }
            break;
        case 'profile':
            if (!$isAuthenticated) {
                header("Location: index.php?page=login");
                exit;
            }
            include "pages/profile.php";
            break;
        default:
            include "pages/home.php";
    }
}
?>

<?php include "includes/header.php"; ?>
<main>
    <?php renderPage($page, $user, $isAuthenticated); ?>
</main>
<?php include "includes/footer.php"; ?>

<!-- Index Page -->