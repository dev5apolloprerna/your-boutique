<?php
// Session Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get logged in user ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get logged in username
function getUsername() {
    return $_SESSION['username'] ?? null;
}

// Get logged in user full name
function getUserFullName() {
    return $_SESSION['full_name'] ?? 'User';
}

// Require login - redirect to login page if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: '. $web_url .'/login.php');
        exit();
    }
}

// Set login session
function setLoginSession($userId, $username, $fullName) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['login_time'] = time();
}

// Logout
function logout() {
    session_unset();
    session_destroy();
    header('Location: '. $web_url.'login.php');
    exit();
}

// Set flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type; // success, error, warning, info
    $_SESSION['flash_message'] = $message;
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
?>
