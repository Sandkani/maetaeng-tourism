<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// กำหนดค่าตัวแปรเริ่มต้น
$location = null;
$images = [];
$videos = [];
$audio_url = null;
$comments = [];
$id = null;
$error_message = '';
$average_rating = 0;

// ตรวจสอบพารามิเตอร์ id
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // ตรวจสอบว่ามีสถานที่นี้จริงหรือไม่
    $sql_check = "SELECT id FROM location1 WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // ดึงข้อมูลสถานที่
        $sql = "SELECT l.*, c.name AS category_name
                FROM location1 l
                LEFT JOIN category c ON l.category_id = c.id
                WHERE l.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $location = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // ดึงค่า viewCount ล่าสุดจากตาราง statistic
        $sql_fetch_views = "SELECT viewCount FROM statistic WHERE locationID = ?";
        $stmt_fetch_views = $conn->prepare($sql_fetch_views);
        $stmt_fetch_views->bind_param("i", $id);
        $stmt_fetch_views->execute();
        $result_views = $stmt_fetch_views->get_result()->fetch_assoc();
        $location['viewCount'] = $result_views['viewCount'] ?? 0;
        $stmt_fetch_views->close();

        // ดึงข้อมูล media
        $sql_media = "SELECT media_type, url FROM media WHERE location_id = ?";
        $stmt_media = $conn->prepare($sql_media);
        $stmt_media->bind_param("i", $id);
        $stmt_media->execute();
        $result_media = $stmt_media->get_result();

        while ($row = $result_media->fetch_assoc()) {
            switch ($row['media_type']) {
                case 'image':
                    $images[] = UPLOAD_URL . 'image/' . basename($row['url']);
                    break;
                case 'video':
                    $videos[] = UPLOAD_URL . 'video/' . basename($row['url']);
                    break;
                case 'audio':
                    $audio_url = UPLOAD_URL . 'audio/' . basename($row['url']);
                    break;
            }
        }
        $stmt_media->close();

        // อัปเดตและดึงจำนวนวิว
        $sql_update_views = "INSERT INTO statistic (locationID, viewCount)
                             VALUES (?, 1)
                             ON DUPLICATE KEY UPDATE viewCount = viewCount + 1";
        $stmt_views = $conn->prepare($sql_update_views);
        if ($stmt_views) {
            $stmt_views->bind_param("i", $id);
            $stmt_views->execute();
            $stmt_views->close();

            // ดึงค่า viewCount ล่าสุดจากตาราง statistic
            $sql_fetch_views = "SELECT viewCount FROM statistic WHERE locationID = ?";
            $stmt_fetch_views = $conn->prepare($sql_fetch_views);
            $stmt_fetch_views->bind_param("i", $id);
            $stmt_fetch_views->execute();
            $result_views = $stmt_fetch_views->get_result()->fetch_assoc();
            $location['viewCount'] = $result_views['viewCount'] ?? 0;
            $stmt_fetch_views->close();
        }

        // ดึงความคิดเห็นและคะแนน
        $sql_comments = "SELECT cm.commentText, u.username, r.rating
                         FROM comment cm
                         JOIN user u ON cm.userID = u.userID
                         LEFT JOIN ratings r
                         ON cm.location_id = r.location_id AND cm.userID = r.user_id
                         WHERE cm.location_id = ?";
        $stmt_comments = $conn->prepare($sql_comments);
        $stmt_comments->bind_param("i", $id);
        $stmt_comments->execute();
        $comments = $stmt_comments->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_comments->close();

        // คำนวณคะแนนเฉลี่ย
        $sql_avg_rating = "SELECT AVG(rating) AS avg_rating FROM ratings WHERE location_id = ?";
        $stmt_avg = $conn->prepare($sql_avg_rating);
        $stmt_avg->bind_param("i", $id);
        $stmt_avg->execute();
        $result_avg = $stmt_avg->get_result()->fetch_assoc();
        $average_rating = $result_avg['avg_rating'] ? round($result_avg['avg_rating'], 1) : 0;
        $stmt_avg->close();
    } else {
        $error_message = "ไม่พบสถานที่ท่องเที่ยวที่คุณต้องการ";
    }
    $stmt_check->close();
}

// จัดการการส่งความคิดเห็น
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['location_id'], $_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        $error_message = "กรุณาเข้าสู่ระบบเพื่อแสดงความคิดเห็น";
    } else {
        $userId = $_SESSION['user_id'];
        $locationId = intval($_POST['location_id']);
        $comment = $_POST['comment'];
        $commentDate = date('Y-m-d');
        $rating = intval($_POST['rating'] ?? 0);

        $conn->begin_transaction();

        $sql_insert_comment = "INSERT INTO comment (location_id, userID, commentText, commentDate)
                               VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert_comment);

        $sql_upsert_rating = "INSERT INTO ratings (location_id, user_id, rating)
                              VALUES (?, ?, ?)
                              ON DUPLICATE KEY UPDATE rating = VALUES(rating)";
        $stmt_rating = $conn->prepare($sql_upsert_rating);

        if ($stmt_insert && $stmt_rating) {
            $stmt_insert->bind_param("iiss", $locationId, $userId, $comment, $commentDate);
            $stmt_rating->bind_param("iii", $locationId, $userId, $rating);

            try {
                $stmt_insert->execute();
                $stmt_rating->execute();
                $conn->commit();
                header("Location: location_detail_controller.php?id=" . $locationId);
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
            }
            $stmt_insert->close();
            $stmt_rating->close();
        }
    }
}

$conn->close();

// *** โค้ดที่เพิ่มใหม่สำหรับการกำหนด URL ของปุ่ม "กลับ" ***
// สมมติว่าหน้าสถิติความนิยมคือ dashboard.php ในโฟลเดอร์ admin
// คุณสามารถเปลี่ยนไปใช้ URL ที่ถูกต้องตามโครงสร้างโปรเจกต์ของคุณ
$back_url = '../public/statistics.php'; 

// เรียก view
require_once __DIR__ . '/location_detail_view.php';
?>
