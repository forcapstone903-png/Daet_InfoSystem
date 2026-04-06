<?php
// logout.php - User Logout
require_once 'dbconn.php';

// ALWAYS start session first if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Update user's online status before logout
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $updateQuery = "UPDATE info_profiles SET is_online = FALSE, last_seen = NOW() WHERE id = $1";
    query($updateQuery, [$user_id]);
}

// === COMPLETE SESSION CLEAR ===

// 1. Unset all session variables
$_SESSION = array();

// 2. Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy session
session_destroy();

// 4. Clear remember me cookie
if (isset($_COOKIE['remember_email'])) {
    setcookie('remember_email', '', time() - 3600, '/');
}

// 5. Clear any other auth cookies
$auth_cookies = ['user_id', 'user_email', 'user_name', 'PHPSESSID'];
foreach ($auth_cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// 6. IMPORTANT: Prevent browser from caching the logged-in state
header("Cache-Control: no-cache, no-store, must-revalidate, private");
header("Pragma: no-cache");
header("Expires: 0");

// 7. Redirect to home.php
header("Location: home.php");
exit();
?>