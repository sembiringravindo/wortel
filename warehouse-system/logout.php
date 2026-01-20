<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    $auth->logActivity($_SESSION['user_id'], "User logged out from system");
}

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remaining session data
session_start();
session_regenerate_id(true);

// Redirect to login page with logout message
$_SESSION['logout_message'] = "Anda telah berhasil logout dari sistem.";
header('Location: login.php');
exit();
?>