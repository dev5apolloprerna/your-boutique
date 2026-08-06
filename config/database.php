<?php
ob_start();
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'getdemo');
define('DB_PASS', 'pjoo*bHxEE0u');
define('DB_NAME', 'getdemo_payal_arban_stichies');
$web_url='https://getdemo.in/your-boutique/';
// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Helper function to execute queries
function executeQuery($conn, $sql, $types = "", $params = []) {
    if (empty($params)) {
        $result = $conn->query($sql);
        return $result;
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        return $stmt;
    }
    
    return $result;
}
?>
