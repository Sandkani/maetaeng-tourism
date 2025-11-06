<?php
// Include database connection file
include '../config/config.php';

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to reset the AUTO_INCREMENT value for the 'media' table
// This query will reset the ID to 1 if the table is empty.
// If there are existing records, it will set the AUTO_INCREMENT to the next available ID.
$sql = "ALTER TABLE media AUTO_INCREMENT = 1";

if ($conn->query($sql) === TRUE) {
    echo "ID ของตาราง media ถูกรีเซ็ตเรียบร้อยแล้ว";
} else {
    echo "เกิดข้อผิดพลาดในการรีเซ็ต ID: " . $conn->error;
}

// Close the database connection
$conn->close();
?>