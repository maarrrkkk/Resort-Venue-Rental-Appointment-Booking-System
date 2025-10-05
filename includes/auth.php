<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function logout() {
    session_destroy();
}
?>