<?php
// Database configuration
$servername = "localhost";
$username = "root";        // Default XAMPP MySQL username
$password = "";            // Default XAMPP MySQL password (empty)
$dbname = "maetaeng_tourism"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8 for Thai language support
$conn->set_charset("utf8");

// Define upload URL constants
define('UPLOAD_URL', '/maetaeng_tourism/admin/uploads/');
define('UPLOAD_PATH', __DIR__ . '/../admin/uploads/');

// Other configuration constants
define('SITE_URL', 'http://localhost/maetaeng_tourism/');
define('ADMIN_URL', 'http://localhost/maetaeng_tourism/admin/');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>