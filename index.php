<?php
// Redirect to login page or dashboard
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header('Location: /dashboard.php');
} else {
    header('Location: /login.php');
}
exit();
?>
