<?php
// รวมไฟล์การเชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../config/config.php';

// คำสั่ง SQL เพื่อดึงค่า ID สูงสุดจากตาราง location1
$sql_max_id = "SELECT MAX(id) AS max_id FROM location1";
$result_max_id = $conn->query($sql_max_id);
$row = $result_max_id->fetch_assoc();
$max_id = $row['max_id'];

// คำนวณค่า auto-increment ใหม่
// ถ้ามีข้อมูลอยู่ (max_id ไม่เป็น null) ให้ตั้งค่าเป็น max_id + 1
// ถ้าไม่มีข้อมูลอยู่ (max_id เป็น null) ให้ตั้งค่าเป็น 1
$next_auto_increment = $max_id ? $max_id + 1 : 1;

// คำสั่ง SQL เพื่อตั้งค่า AUTO_INCREMENT โดยใช้ค่าที่คำนวณได้โดยตรง
$sql_alter = "ALTER TABLE location1 AUTO_INCREMENT = " . $next_auto_increment;

if ($conn->query($sql_alter) === TRUE) {
    echo "<h1>✅ รีเซ็ตตัวนับ ID สถานที่สำเร็จแล้ว!</h1>";
    echo "<p>ค่า AUTO_INCREMENT ถูกตั้งค่าเป็น " . $next_auto_increment . " เพื่อให้การเพิ่มข้อมูลใหม่เป็นไปอย่างราบรื่น</p>";
} else {
    echo "<h1>❌ เกิดข้อผิดพลาดในการรีเซ็ต ID: " . $conn->error . "</h1>";
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>