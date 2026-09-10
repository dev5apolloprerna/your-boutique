<?php
// Redirect to login page or dashboard
require_once __DIR__ . '/config/session.php';
$web_url='http://localhost/your-boutique/';
if (isLoggedIn()) {
    header('Location: '. $web_url .'/dashboard.php');
} else {
    
    header('Location: '. $web_url .'/login.php');
}
exit();
?>
