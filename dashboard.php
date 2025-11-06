<?php
// Include database connection file and start a session
include '../config/config.php';
session_start();

// Check if the user is an admin and logged in; redirect to login page if not
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Set up the directory for media uploads
$upload_dir = __DIR__ . "/uploads/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// **New code for handling media deletion from both the server and database**
if (isset($_GET['action']) && $_GET['action'] == 'delete_media') {
    $media_id = $_GET['id'] ?? null;

    if ($media_id) {
        // Fetch the file URL from the database
        $sql_select = "SELECT url FROM media WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $media_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $media = $result->fetch_assoc();
        $stmt_select->close();

        if ($media) {
            $file_path = __DIR__ . '/../' . $media['url'];
            
            // Delete the physical file from the server's directory
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Delete the corresponding record from the database
            $sql_delete = "DELETE FROM media WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $media_id);
            
            if ($stmt_delete->execute()) {
                $_SESSION['message'] = "ลบสื่อสำเร็จ ✅";
            } else {
                $_SESSION['message'] = "เกิดข้อผิดพลาดในการลบสื่อ: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
            $_SESSION['message'] = "ไม่พบสื่อที่ต้องการลบ";
        }
    } else {
        $_SESSION['message'] = "ไม่พบ ID ของสื่อที่ต้องการลบ";
    }

    header("Location: dashboard.php");
    exit();
}
// **End of new code**

/**
 * Handles the upload of a media file and inserts a record into the database.
 *
 * @param mysqli $conn The database connection object.
 * @param int $location_id The ID of the location to associate with the media.
 * @param array $file The file array from $_FILES.
 * @param string $upload_dir The destination directory for the uploaded file.
 * @return bool True on successful upload and database insert, false otherwise.
 */
function handle_media_upload($conn, $location_id, $file, $upload_dir) {
    if ($file['error'] == UPLOAD_ERR_OK) {
        $file_name = uniqid() . '-' . basename($file['name']);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (strpos($mime_type, 'image') !== false) {
            $media_type = 'image';
            $subdirectory = 'image/';
        } elseif (strpos($mime_type, 'video') !== false) {
            $media_type = 'video';
            $subdirectory = 'video/';
        } elseif (strpos(strtolower($mime_type), 'audio') !== false) {
            $media_type = 'audio';
            $subdirectory = 'audio/';
        } else {
            return false;
        }

        // Create the subdirectory if it doesn't exist
        if (!is_dir($upload_dir . $subdirectory)) {
            mkdir($upload_dir . $subdirectory, 0755, true);
        }

        $file_path = $upload_dir . $subdirectory . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $file_url = 'admin/uploads/' . $subdirectory . $file_name;
            
            $stmt_media = $conn->prepare("INSERT INTO media (location_id, media_type, url) VALUES (?, ?, ?)");
            if ($stmt_media) {
                $stmt_media->bind_param("iss", $location_id, $media_type, $file_url);
                $stmt_media->execute();
                $stmt_media->close();
            } else {
                error_log("Failed to prepare media insert statement: " . $conn->error);
            }
            return true;
        } else {
            error_log("Failed to move uploaded file to: " . $file_path);
        }
    }
    return false;
}

// Handle form submissions for adding/deleting/editing locations and categories
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $message = '';

    if ($action == 'add_location') {
        // Handle adding a new location
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        $last_id = null;

        $conn->begin_transaction();
        $success = true;

        $stmt = $conn->prepare("INSERT INTO location1 (name, description, category_id, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssiss", $name, $description, $category_id, $latitude, $longitude);
            if ($stmt->execute()) {
                $last_id = $conn->insert_id;
                $message = "เพิ่มสถานที่สำเร็จ 🌟";
            } else {
                $success = false;
                $message = "เพิ่มสถานที่ไม่สำเร็จ: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $success = false;
            $message = "Error preparing statement: " . $conn->error;
        }

        if ($success && $last_id) {
            // Corrected handling of multiple files
            if (isset($_FILES['image_file']) && is_array($_FILES['image_file']['name'])) {
                foreach ($_FILES['image_file']['tmp_name'] as $index => $tmp_name) {
                    $file_to_upload = [
                        'name' => $_FILES['image_file']['name'][$index],
                        'type' => $_FILES['image_file']['type'][$index],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['image_file']['error'][$index],
                        'size' => $_FILES['image_file']['size'][$index]
                    ];
                    handle_media_upload($conn, $last_id, $file_to_upload, $upload_dir);
                }
            }
            if (isset($_FILES['video_file']) && is_array($_FILES['video_file']['name'])) {
                foreach ($_FILES['video_file']['tmp_name'] as $index => $tmp_name) {
                    $file_to_upload = [
                        'name' => $_FILES['video_file']['name'][$index],
                        'type' => $_FILES['video_file']['type'][$index],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['video_file']['error'][$index],
                        'size' => $_FILES['video_file']['size'][$index]
                    ];
                    handle_media_upload($conn, $last_id, $file_to_upload, $upload_dir);
                }
            }
            if (isset($_FILES['audio_file']) && is_array($_FILES['audio_file']['name'])) {
                foreach ($_FILES['audio_file']['tmp_name'] as $index => $tmp_name) {
                    $file_to_upload = [
                        'name' => $_FILES['audio_file']['name'][$index],
                        'type' => $_FILES['audio_file']['type'][$index],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['audio_file']['error'][$index],
                        'size' => $_FILES['audio_file']['size'][$index]
                    ];
                    handle_media_upload($conn, $last_id, $file_to_upload, $upload_dir);
                }
            }
        }

        if ($success) {
            $conn->commit();
        } else {
            $conn->rollback();
        }

    } elseif ($action == 'delete_location') {
        // Handle deleting a location
        $location_id = $_GET['id'] ?? null;
        if ($location_id) {
            $conn->begin_transaction();
            $success = true;

            // Delete associated media files from the server first
            $stmt_media_urls = $conn->prepare("SELECT url FROM media WHERE location_id = ?");
            $stmt_media_urls->bind_param("i", $location_id);
            $stmt_media_urls->execute();
            $result_media_urls = $stmt_media_urls->get_result();
            while ($row = $result_media_urls->fetch_assoc()) {
                $file_path = __DIR__ . "/../" . $row['url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            $stmt_media_urls->close();
            
            // Delete media records from the database
            $stmt_delete_media = $conn->prepare("DELETE FROM media WHERE location_id = ?");
            $stmt_delete_media->bind_param("i", $location_id);
            if (!$stmt_delete_media->execute()) {
                $success = false;
                $message = "Error deleting media: " . $stmt_delete_media->error;
            }
            $stmt_delete_media->close();

            // Delete location record
            $stmt_delete_location = $conn->prepare("DELETE FROM location1 WHERE id = ?");
            $stmt_delete_location->bind_param("i", $location_id);
            if (!$stmt_delete_location->execute()) {
                $success = false;
                $message = "Error deleting location: " . $stmt_delete_location->error;
            }
            $stmt_delete_location->close();

            // Delete related statistics
            $stmt_delete_stats = $conn->prepare("DELETE FROM statistic WHERE locationID = ?");
            $stmt_delete_stats->bind_param("i", $location_id);
            if (!$stmt_delete_stats->execute()) {
                $success = false;
                $message = "Error deleting stats: " . $stmt_delete_stats->error;
            }
            $stmt_delete_stats->close();
            
            if ($success) {
                $conn->commit();
                $message = "ลบสถานที่สำเร็จ 🗑️";
            } else {
                $conn->rollback();
                $message = $message ?: "ลบสถานที่และข้อมูลไม่สำเร็จ";
            }
        }
    } elseif ($action == 'edit_location') {
        // Handle editing an existing location
        $location_id = $_POST['location_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;

        if ($location_id) {
            $conn->begin_transaction();
            $success = true;

            // Update location's text data
            $stmt = $conn->prepare("UPDATE location1 SET name = ?, description = ?, category_id = ?, latitude = ?, longitude = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssissi", $name, $description, $category_id, $latitude, $longitude, $location_id);
                if (!$stmt->execute()) {
                    $success = false;
                    $message = "แก้ไขข้อมูลสถานที่ไม่สำเร็จ: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $success = false;
                $message = "Error preparing location update statement: " . $conn->error;
            }
            
            // Handle new media uploads for the edited location
            if ($success) {
                if (isset($_FILES['image_file']) && is_array($_FILES['image_file']['name'])) {
                    foreach ($_FILES['image_file']['tmp_name'] as $index => $tmp_name) {
                        $file_to_upload = [
                            'name' => $_FILES['image_file']['name'][$index],
                            'type' => $_FILES['image_file']['type'][$index],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['image_file']['error'][$index],
                            'size' => $_FILES['image_file']['size'][$index]
                        ];
                        if ($file_to_upload['error'] == UPLOAD_ERR_OK) {
                            handle_media_upload($conn, $location_id, $file_to_upload, $upload_dir);
                        }
                    }
                }
                if (isset($_FILES['video_file']) && is_array($_FILES['video_file']['name'])) {
                    foreach ($_FILES['video_file']['tmp_name'] as $index => $tmp_name) {
                        $file_to_upload = [
                            'name' => $_FILES['video_file']['name'][$index],
                            'type' => $_FILES['video_file']['type'][$index],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['video_file']['error'][$index],
                            'size' => $_FILES['video_file']['size'][$index]
                        ];
                        if ($file_to_upload['error'] == UPLOAD_ERR_OK) {
                            handle_media_upload($conn, $location_id, $file_to_upload, $upload_dir);
                        }
                    }
                }
                if (isset($_FILES['audio_file']) && is_array($_FILES['audio_file']['name'])) {
                    foreach ($_FILES['audio_file']['tmp_name'] as $index => $tmp_name) {
                        $file_to_upload = [
                            'name' => $_FILES['audio_file']['name'][$index],
                            'type' => $_FILES['audio_file']['type'][$index],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['audio_file']['error'][$index],
                            'size' => $_FILES['audio_file']['size'][$index]
                        ];
                        if ($file_to_upload['error'] == UPLOAD_ERR_OK) {
                            handle_media_upload($conn, $location_id, $file_to_upload, $upload_dir);
                        }
                    }
                }
            }


            if ($success) {
                $conn->commit();
                $message = "แก้ไขสถานที่สำเร็จ ✅";
            } else {
                $conn->rollback();
                $message = $message ?: "แก้ไขสถานที่ไม่สำเร็จ";
            }
        }

    } elseif ($action == 'add_category') {
        // Handle adding a new category
        $category_name = $_POST['category_name'];
        $stmt = $conn->prepare("INSERT INTO category (name) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $category_name);
            if ($stmt->execute()) {
                $message = "เพิ่มหมวดหมู่สำเร็จ 🏷️";
            } else {
                $message = "เพิ่มหมวดหมู่ไม่สำเร็จ: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action == 'delete_category') {
        // Handle deleting a category
        $category_id = $_POST['category_id'];
        $stmt = $conn->prepare("DELETE FROM category WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $category_id);
            if ($stmt->execute()) {
                $message = "ลบหมวดหมู่สำเร็จ 🗑️";
            } else {
                $message = "ลบหมวดหมู่ไม่สำเร็จ: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Redirect to the dashboard page to prevent form resubmission
    $_SESSION['message'] = $message;
    header("Location: dashboard.php");
    exit();
}

// --- Data Fetching Section ---

// 1. Fetch all statistics data to calculate totals
$all_stats = [];
$sql_all_stats = "SELECT s.viewCount, l.name, l.id
                  FROM statistic s
                  JOIN location1 l ON s.locationID = l.id
                  ORDER BY s.viewCount DESC";
$result_all_stats = $conn->query($sql_all_stats);
if ($result_all_stats) {
    while ($row = $result_all_stats->fetch_assoc()) {
        $all_stats[] = $row;
    }
}

// 2. Fetch Top 3 locations based on viewCount
$top_locations = [];
$sql_top_3 = "SELECT
                l.id,
                l.name,
                s.viewCount,
                (SELECT url FROM media WHERE location_id = l.id AND media_type = 'image' LIMIT 1) AS image_url
              FROM statistic s
              JOIN location1 l ON s.locationID = l.id
              ORDER BY s.viewCount DESC
              LIMIT 3";

$result_top_3 = $conn->query($sql_top_3);
if ($result_top_3) {
    while ($row = $result_top_3->fetch_assoc()) {
        $top_locations[] = $row;
    }
}


// Fetch all locations from the database
$locations = [];
$sql_locations = "SELECT l.*, c.name AS category_name FROM location1 l LEFT JOIN category c ON l.category_id = c.id ORDER BY l.id DESC";
$result_locations = $conn->query($sql_locations);
if ($result_locations) {
    $locations = $result_locations->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error fetching locations: " . $conn->error);
}

// Fetch all categories
$categories = [];
$sql_categories = "SELECT * FROM category ORDER BY id ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories) {
    $categories = $result_categories->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error fetching categories: " . $conn->error);
}

// Fetch comments and ratings
$comments_and_ratings = [];
$sql_comments = "SELECT
    cm.commentID AS id,
    'comment' AS type,
    cm.commentText AS text,
    cm.created_at AS timestamp,
    l.name AS location_name
FROM comment cm
JOIN location1 l ON cm.location_id = l.id

UNION ALL

SELECT
    r.id AS id,
    'rating' AS type,
    r.rating AS text,
    r.created_at AS timestamp,
    l.name AS location_name
FROM ratings r
JOIN location1 l ON r.location_id = l.id

ORDER BY timestamp DESC
LIMIT 10";

$result_comments = $conn->query($sql_comments);

if ($result_comments) {
    while ($row = $result_comments->fetch_assoc()) {
        $comments_and_ratings[] = $row;
    }
} else {
    error_log("Error fetching comments and ratings: " . $conn->error);
}

// Fetch media for each location
foreach ($locations as &$location) {
    $media_sql = "SELECT url, media_type, id FROM media WHERE location_id = ? ORDER BY media_type DESC, id ASC";
    $stmt_media = $conn->prepare($media_sql);
    $stmt_media->bind_param("i", $location['id']);
    $stmt_media->execute();
    $result_media = $stmt_media->get_result();
    $location['media'] = $result_media->fetch_all(MYSQLI_ASSOC);
    $stmt_media->close();
}
unset($location); // Unset the reference to avoid issues with subsequent loops

$conn->close();
?>
<!-- hpml -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ดูแลระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; }
        .container { max-width: 1200px; }
        .tab-content { display: none; opacity: 0; transform: translateY(20px); transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out; }
        .tab-content.active { display: block; opacity: 1; transform: translateY(0); }
        .tab-button { transition: all 0.3s ease; position: relative; overflow: hidden; }
        .tab-button.active { background-color: white; color: #10B981; }
        .tab-button::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background-color: transparent; transition: all 0.3s ease; }
        .tab-button.active::after { background-color: #10B981; }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        .animate-fade-in { animation: fade-in 0.5s ease-in-out; }
        .location-image { max-width: 80px; height: auto; border-radius: 4px; }
          /* css */
        /* Figma Design Inspired Styles */
        .card-3d {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 10px 15px rgba(0, 0, 0, 0.05);
        }
        .card-3d:hover {
            transform: translateY(-5px) scale(1.02) translateZ(10px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.15), 0 20px 25px rgba(0, 0, 0, 0.1);
        }

        /* Top 3 Card Animation */
        @keyframes slide-up {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-up {
            animation: slide-up 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            opacity: 0;
        }
        .animate-slide-up:nth-child(1) { animation-delay: 0.1s; }
        .animate-slide-up:nth-child(2) { animation-delay: 0.2s; }
        .animate-slide-up:nth-child(3) { animation-delay: 0.3s; }

        /* Modal styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

         /* New styles for location cards */
         .location-card {
            display: flex;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .location-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .location-image-container {
            width: 250px; /* Adjusted size */
            flex-shrink: 0;
            position: relative;
        }
        .location-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .location-details {
            padding: 24px;
            flex-grow: 1;
        }
        .location-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }
        .location-category {
            font-size: 0.875rem;
            color: #4c51bf;
            background-color: #e2e8f0;
            padding: 4px 8px;
            border-radius: 9999px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 12px;
        }
        .location-description {
            font-size: 1rem;
            color: #4a5568;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .location-actions {
            display: flex;
            gap: 12px;
            margin-top: auto; /* Push actions to the bottom */
        }

        /* Detail Modal */
        .modal-large-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 800px; /* Increased max-width */
            animation: slideIn 0.3s ease-out;
            max-height: 90vh; /* Limit height */
            overflow-y: auto; /* Enable scrolling */
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        .media-grid-item {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
    </style>
<header class="bg-emerald-600 text-white p-6 shadow-md rounded-xl header-container">
    <div class="container mx-auto flex justify-between items-center">
        <a href="dashboard.php" class="text-3xl font-bold tracking-wide transition-transform hover:scale-105">
            <i class="fas fa-chart-line"></i> ระบบสารสนเทศผู้ดูแลระบบ
        </a>
        <nav class="flex items-center space-x-4">
            <a href="profile.php" class="text-white py-2 px-4 rounded-full font-bold hover:bg-emerald-700 transition-colors">
                <i class="fas fa-user-circle mr-2"></i> โปรไฟล์
            </a>
            <a href="logout.php" class="bg-white text-emerald-600 py-2 px-4 rounded-full font-bold hover:bg-gray-200 transition-colors shadow-md">
                ออกจากระบบ <i class="fas fa-sign-out-alt ml-2"></i>
            </a>
        </nav>
    </div>
    <div class="cartoon-character"></div>
</header>


    <main class="container mx-auto mt-8 p-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate-fade-in" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></span>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-2 mb-6 bg-gray-200 rounded-xl p-1 shadow-inner">
            <button id="tab-reports-btn" onclick="openTab('reports', this)" class="tab-button flex-1 py-3 px-6 text-lg font-semibold text-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-transform transform hover:scale-105 active">
                <i class="fas fa-chart-bar mr-2"></i> รายงานและสถิติ
            </button>
            <button id="tab-locations-btn" onclick="openTab('locations', this)" class="tab-button flex-1 py-3 px-6 text-lg font-semibold text-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-transform transform hover:scale-105">
                <i class="fas fa-map-marked-alt mr-2"></i> จัดการสถานที่
            </button>
            <button id="tab-categories-btn" onclick="openTab('categories', this)" class="tab-button flex-1 py-3 px-6 text-lg font-semibold text-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-transform transform hover:scale-105">
                <i class="fas fa-tags mr-2"></i> หมวดหมู่
            </button>
            <button id="tab-comments-btn" onclick="openTab('comments', this)" class="tab-button flex-1 py-3 px-6 text-lg font-semibold text-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-transform transform hover:scale-105">
                <i class="fas fa-comments mr-2"></i> ความคิดเห็นและคะแนน
            </button>
        </div>

        <div id="reports" class="tab-content bg-white rounded-lg shadow-lg p-6 active">
    <h2 class="text-3xl font-bold text-indigo-600 mb-6 border-b-2 border-indigo-200 pb-2 flex items-center">
        <i class="fas fa-chart-bar mr-3 text-2xl animate-pulse text-indigo-500"></i> รายงานและสถิติ
    </h2>

    <section class="mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="card-glow-blue cursor-pointer rounded-lg p-6 shadow-xl transform transition-all duration-500 ease-in-out hover:scale-105 hover:translate-y-[-5px] animate-fade-in-up">
                <div class="flex items-center justify-between">
                    <i class="fas fa-map-marked-alt text-4xl text-white opacity-75"></i>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-white opacity-80">จำนวนสถานที่ทั้งหมด</p>
                        <p class="text-4xl font-bold text-white"><?php echo count($all_stats); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-glow-gold cursor-pointer rounded-lg p-6 shadow-xl transform transition-all duration-500 ease-in-out hover:scale-105 hover:translate-y-[-5px] animate-fade-in-up delay-100">
                <div class="flex items-center justify-between">
                    <i class="fas fa-eye text-4xl text-white opacity-75"></i>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-white opacity-80">ยอดเข้าชมรวมทั้งหมด</p>
                        <p class="text-4xl font-bold text-white"><?php echo number_format(array_sum(array_column($all_stats, 'viewCount'))); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-glow-blue cursor-pointer rounded-lg p-6 shadow-xl transform transition-all duration-500 ease-in-out hover:scale-105 hover:translate-y-[-5px] animate-fade-in-up delay-200">
                <div class="flex items-center justify-between">
                    <i class="fas fa-award text-4xl text-white opacity-75"></i>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-white opacity-80">สถานที่ยอดนิยมที่สุด</p>
                        <p class="text-xl font-bold text-white">
                            <?php echo !empty($top_locations) ? htmlspecialchars($top_locations[0]['name']) : 'ไม่มีข้อมูล'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
            
    <section class="mb-12">
        <h2 class="text-3xl font-bold text-center text-gray-700 mb-6 flex items-center justify-center">
            <i class="fas fa-trophy mr-3 text-3xl text-yellow-500 animate-bounce"></i> 🏆 สถานที่ยอดนิยม (Top 3)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php if (!empty($top_locations)): ?>
                <?php foreach ($top_locations as $index => $location): ?>
                    <div class="location-card relative block rounded-xl overflow-hidden shadow-lg 
                                transform transition-all duration-500 ease-in-out hover:scale-[1.05] hover:translate-y-[-5px] 
                                <?php 
                                    if ($index == 0) {
                                        echo 'card-glow-gold';
                                    } elseif ($index == 1) {
                                        echo 'card-glow-blue';
                                    } else {
                                        echo 'card-glow-green';
                                    }
                                ?> 
                                animate-fade-in-up-delay-<?php echo $index; ?>">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent opacity-80 z-10"></div>
                        <img src="../<?php echo htmlspecialchars($location['image_url'] ?: 'placeholders/no-image.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($location['name']); ?>" 
                             class="w-full h-full object-cover transform transition-all duration-500 ease-in-out group-hover:scale-110">
                        <div class="absolute inset-0 z-20 flex flex-col justify-end p-6">
                            <h3 class="text-2xl font-bold text-white tracking-wide"><?php echo htmlspecialchars($location['name']); ?></h3>
                            <p class="text-white text-base mt-1 opacity-90">ยอดเข้าชม: <?php echo number_format($location['viewCount']); ?> ครั้ง</p>
                        </div>
                        <div class="absolute top-4 right-4 flex items-center justify-center w-14 h-14 rounded-full font-bold text-2xl shadow-xl z-30
                                    transform transition-all duration-300 ease-in-out hover:scale-125
                                    <?php echo ($index == 0) ? 'bg-yellow-400 text-white' : (($index == 1) ? 'bg-blue-400 text-white' : 'bg-green-400 text-white'); ?>">
                            #<?php echo $index + 1; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="md:col-span-3 text-center text-gray-500 italic py-10">
                    <p>ไม่มีข้อมูลสถิติสถานที่ยอดนิยมในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <section>
    <h3 class="text-2xl font-bold text-gray-700 mb-4 flex items-center">
        <i class="fas fa-chart-line mr-2"></i> 📊 สถิติยอดเข้าชมทั้งหมด
    </h3>
    <div class="overflow-hidden rounded-lg shadow-lg bg-white table-glow-effect">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                        อันดับ
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                        ชื่อสถานที่
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                        ยอดเข้าชม
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($all_stats)): ?>
                    <?php foreach ($all_stats as $index => $stat): ?>
                        <tr class="hover:bg-indigo-50 transition-colors duration-200 animate-fade-in">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $index + 1; ?></td>
                            <td class="px-6 py-4 whitespace-now-wrap text-sm text-gray-600"><?php echo htmlspecialchars($stat['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-indigo-600"><?php echo number_format($stat['viewCount']); ?> ครั้ง</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 italic">ไม่มีข้อมูลสถิติยอดเข้าชม</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fade-in 0.5s ease-out forwards;
    }

   /* New CSS for the Cartoon Effect */
.header-container {
    position: relative;
    overflow: hidden; /* ซ่อนส่วนที่เกินขอบของรูปการ์ตูน */
}

.cartoon-character {
    position: absolute;
    bottom: -80px; /* ซ่อนตัวการ์ตูนไว้ด้านล่าง */
    right: 20px; /* ตำแหน่งด้านขวา */
    width: 100px;
    height: 100px;
    background-image: url('https://cdn.pixabay.com/animation/2023/01/14/02/11/02-11-52-658_512.gif'); /* **ลิงก์รูปภาพการ์ตูน** */
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    transition: all 0.5s ease-in-out;
    opacity: 0;
    transform: rotateY(0deg);
}

.header-container:hover .cartoon-character {
    bottom: 0px; /* เลื่อนตัวการ์ตูนขึ้นมา */
    opacity: 1;
    transform: rotateY(360deg); /* หมุน 360 องศา */
}
</style>

    <style>
        /* General Glow Cards */
        .card-glow-blue {
            background: linear-gradient(145deg, #002eaeff, #d0ae02ff);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.5s ease-in-out, box-shadow 0.5s ease-in-out;
        }
        
        .card-glow-blue:hover {
            transform: scale(1.05) translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.5), 0 6px 12px -3px rgba(59, 130, 246, 0.25);
        }
        
        .card-glow-gold {
            background: linear-gradient(145deg, #ffc107, #048000ff);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.5s ease-in-out, box-shadow 0.5s ease-in-out;
        }
        
        .card-glow-gold:hover {
            transform: scale(1.05) translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(245, 158, 11, 0.5), 0 6px 12px -3px rgba(245, 158, 11, 0.25);
        }

        /* Top Locations Specific Glow Card */
        .card-glow-green {
            background: linear-gradient(145deg, #48bb78, #38a169); /* Green Gradient */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.5s ease-in-out, box-shadow 0.5s ease-in-out;
        }

        .card-glow-green:hover {
            transform: scale(1.05) translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(76, 175, 80, 0.5), 0 6px 12px -3px rgba(76, 175, 80, 0.25);
        }

        /* Location Card Hover Effect */
        .location-card:hover {
            transform: scale(1.05) translate-y(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.25), 0 6px 12px -3px rgba(0, 0, 0, 0.1);
        }

        .location-card.card-glow-gold:hover {
            box-shadow: 0 15px 30px -5px rgba(245, 158, 11, 0.5), 0 6px 12px -3px rgba(245, 158, 11, 0.25);
        }

        .location-card.card-glow-blue:hover {
            box-shadow: 0 15px 30px -5px rgba(2, 66, 169, 0.5), 0 6px 12px -3px rgba(59, 130, 246, 0.25);
        }

        .location-card.card-glow-green:hover {
            box-shadow: 0 15px 30px -5px rgba(76, 175, 80, 0.5), 0 6px 12px -3px rgba(76, 175, 80, 0.25);
        }

        /* Animation for the bounce effect on the icon */
        @keyframes bounce {
            0%, 100% {
                transform: translateY(-25%);
                animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
            }
            50% {
                transform: none;
                animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
            }
        }
        .animate-bounce {
            animation: bounce 1s infinite;
        }

        /* Custom keyframes for delayed fade-in-up animation */
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.6s ease-out forwards;
        }

        .animate-fade-in-up-delay-100 {
            animation: fade-in-up 0.6s ease-out 0.1s forwards;
            opacity: 0;
        }
        .animate-fade-in-up-delay-200 {
            animation: fade-in-up 0.6s ease-out 0.2s forwards;
            opacity: 0;
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.5s ease-out forwards;
        }
        <?php for ($i = 0; $i < 3; $i++) { echo "@keyframes fade-in-up-delay-{$i} { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-in-up-delay-{$i} { animation: fade-in-up-delay-{$i} 0.6s ease-out forwards; animation-delay: " . (0.1 * $i) . "s; opacity: 0; }"; } ?>
    </style>
</div>

<div id="locations" class="tab-content bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-3xl font-bold text-emerald-600 mb-6 border-b-2 border-emerald-200 pb-2 flex items-center">
        <i class="fas fa-map-marked-alt mr-3"></i> จัดการสถานที่
        <button onclick="openAddModal()" class="ml-auto bg-emerald-500 text-white px-4 py-2 rounded-full font-semibold hover:bg-emerald-600 transition-colors shadow-md">
            <i class="fas fa-plus-circle mr-2"></i> เพิ่มสถานที่ใหม่
        </button>
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
        <?php if (!empty($locations)): ?>
            <?php foreach ($locations as $location): ?>
                <div class="location-card">
                    <div class="location-image-container">
                        <?php 
                            $main_image_url = 'placeholders/no-image.jpg';
                            foreach ($location['media'] as $media) {
                                if ($media['media_type'] === 'image') {
                                    $main_image_url = $media['url'];
                                    break;
                                }
                            }
                        ?>
                        <img src="../<?php echo htmlspecialchars($main_image_url); ?>" alt="<?php echo htmlspecialchars($location['name']); ?>">
                    </div>
                    <div class="location-details flex flex-col">
                        <h3 class="location-title"><?php echo htmlspecialchars($location['name']); ?></h3>
                        <span class="location-category"><?php echo htmlspecialchars($location['category_name'] ?? 'ไม่มีหมวดหมู่'); ?></span>
                        <p class="location-description text-gray-700">
                            <?php echo htmlspecialchars(mb_substr($location['description'], 0, 150, 'UTF-8')); ?>...
                        </p>
                        <div class="location-actions">
                            <button onclick='openDetailModal(<?php echo json_encode($location); ?>)' class="bg-blue-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-600 transition-colors">
                                <i class="fas fa-info-circle mr-2"></i>ดูข้อมูล
                            </button>
                            <button onclick='openEditModal(<?php echo json_encode($location); ?>)' class="bg-indigo-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-600 transition-colors">
                                <i class="fas fa-edit mr-2"></i>แก้ไข
                            </button>
                            <a href="dashboard.php?action=delete_location&id=<?php echo $location['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600 transition-colors" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบสถานที่นี้? การกระทำนี้ไม่สามารถยกเลิกได้');">
                                <i class="fas fa-trash-alt mr-2"></i>ลบ
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="md:col-span-2 text-center text-gray-500 italic py-10">
                <p>ไม่มีข้อมูลสถานที่ในขณะนี้</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="categories" class="tab-content bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-3xl font-bold text-purple-600 mb-6 border-b-2 border-purple-200 pb-2 flex items-center">
        <i class="fas fa-tags mr-3"></i> จัดการหมวดหมู่
        <button onclick="openAddCategoryModal()" class="ml-auto bg-purple-500 text-white px-4 py-2 rounded-full font-semibold hover:bg-purple-600 transition-colors shadow-md">
            <i class="fas fa-plus-circle mr-2"></i> เพิ่มหมวดหมู่
        </button>
    </h2>

    <div class="overflow-x-auto rounded-lg shadow-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        ชื่อหมวดหมู่
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                        การจัดการ
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($category['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="dashboard.php?action=delete_category&id=<?php echo $category['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบหมวดหมู่นี้? การกระทำนี้ไม่สามารถยกเลิกได้');"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 italic">ไม่มีหมวดหมู่ในขณะนี้</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="comments" class="tab-content bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-3xl font-bold text-gray-700 mb-6 border-b-2 border-gray-200 pb-2 flex items-center">
        <i class="fas fa-comments mr-3"></i> ความคิดเห็นและคะแนนล่าสุด
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($comments_and_ratings)): ?>
            <?php foreach ($comments_and_ratings as $item): ?>
                <div class="p-6 bg-white rounded-lg shadow-lg border-2 border-gray-200 animate-fade-in-up">
                    <div class="flex items-start mb-4">
                        <?php if ($item['type'] === 'comment'): ?>
                            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 mr-4">
                                <i class="fas fa-comment text-xl"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-semibold text-gray-500">ความคิดเห็น</p>
                                <h3 class="text-xl font-bold text-gray-800 break-words"><?php echo htmlspecialchars($item['text']); ?></h3>
                            </div>
                        <?php else: ?>
                            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <i class="fas fa-star text-xl"></i>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-semibold text-gray-500">คะแนน</p>
                                <div class="flex items-center">
                                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($item['text']); ?>/5</h3>
                                    <?php 
                                        $rating = (float) $item['text'];
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $rating):
                                    ?>
                                        <i class="fas fa-star text-yellow-400 ml-1"></i>
                                    <?php else: ?>
                                        <i class="far fa-star text-gray-300 ml-1"></i>
                                    <?php endif; endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="text-gray-600">
                        <span class="font-bold">สถานที่:</span> <?php echo htmlspecialchars($item['location_name']); ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-2">
                        <?php echo date('d M Y, H:i:s', strtotime($item['timestamp'])); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="md:col-span-3 text-center text-gray-500 italic py-10">
                <p>ไม่มีข้อมูลความคิดเห็นหรือคะแนนล่าสุด</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="add-location-modal" class="modal hidden">
    <div class="modal-content">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-2xl font-bold text-emerald-600">เพิ่มสถานที่ใหม่</h3>
            <span class="text-gray-500 text-2xl cursor-pointer" onclick="closeAddModal()">&times;</span>
        </div>
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_location">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-semibold mb-2">ชื่อสถานที่</label>
                <input type="text" id="name" name="name" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700 font-semibold mb-2">คำอธิบาย</label>
                <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" required></textarea>
            </div>
            <div class="mb-4">
                <label for="category_id" class="block text-gray-700 font-semibold mb-2">หมวดหมู่</label>
                <select id="category_id" name="category_id" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                    <option value="">เลือกหมวดหมู่</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="latitude" class="block text-gray-700 font-semibold mb-2">ละติจูด</label>
                    <input type="text" id="latitude" name="latitude" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                </div>
                <div>
                    <label for="longitude" class="block text-gray-700 font-semibold mb-2">ลองจิจูด</label>
                    <input type="text" id="longitude" name="longitude" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="image_file" class="block text-gray-700 font-semibold mb-2">อัปโหลดรูปภาพ</label>
                <input type="file" id="image_file" name="image_file[]" class="w-full text-gray-700" multiple>
            </div>
            <div class="mb-4">
                <label for="video_file" class="block text-gray-700 font-semibold mb-2">อัปโหลดวิดีโอ</label>
                <input type="file" id="video_file" name="video_file[]" class="w-full text-gray-700" multiple>
            </div>
            <div class="mb-4">
                <label for="audio_file" class="block text-gray-700 font-semibold mb-2">อัปโหลดเสียง</label>
                <input type="file" id="audio_file" name="audio_file[]" class="w-full text-gray-700" multiple>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-emerald-500 text-white px-6 py-2 rounded-md font-semibold hover:bg-emerald-600 transition-colors">บันทึกสถานที่</button>
            </div>
        </form>
    </div>
</div>

<div id="detail-modal" class="modal hidden">
    <div class="modal-large-content">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-2xl font-bold text-blue-600">ข้อมูลสถานที่</h3>
            <span class="text-gray-500 text-2xl cursor-pointer" onclick="closeDetailModal()">&times;</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-xl font-bold mb-2">รายละเอียด</h4>
                <p class="mb-2"><span class="font-semibold">ชื่อ:</span> <span id="detail-name"></span></p>
                <p class="mb-2"><span class="font-semibold">หมวดหมู่:</span> <span id="detail-category"></span></p>
                <p class="mb-2"><span class="font-semibold">คำอธิบาย:</span> <span id="detail-description"></span></p>
                <p class="mb-2"><span class="font-semibold">ละติจูด:</span> <span id="detail-latitude"></span></p>
                <p class="mb-2"><span class="font-semibold">ลองจิจูด:</span> <span id="detail-longitude"></span></p>
            </div>
            <div>
                <h4 class="text-xl font-bold mb-2">สื่อประกอบ</h4>
                <div id="detail-media-container" class="media-grid">
                    </div>
            </div>
        </div>
    </div>
</div>

<div id="edit-modal" class="modal hidden">
    <div class="modal-content">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-2xl font-bold text-indigo-600">แก้ไขสถานที่</h3>
            <span class="text-gray-500 text-2xl cursor-pointer" onclick="closeEditModal()">&times;</span>
        </div>
        <form action="dashboard.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_location">
            <input type="hidden" id="edit-location-id" name="location_id">
            <div class="mb-4">
                <label for="edit-name" class="block text-gray-700 font-semibold mb-2">ชื่อสถานที่</label>
                <input type="text" id="edit-name" name="name" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
            </div>
            <div class="mb-4">
                <label for="edit-description" class="block text-gray-700 font-semibold mb-2">คำอธิบาย</label>
                <textarea id="edit-description" name="description" rows="3" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required></textarea>
            </div>
            <div class="mb-4">
                <label for="edit-category-id" class="block text-gray-700 font-semibold mb-2">หมวดหมู่</label>
                <select id="edit-category-id" name="category_id" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">เลือกหมวดหมู่</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="edit-latitude" class="block text-gray-700 font-semibold mb-2">ละติจูด</label>
                    <input type="text" id="edit-latitude" name="latitude" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
                <div>
                    <label for="edit-longitude" class="block text-gray-700 font-semibold mb-2">ลองจิจูด</label>
                    <input type="text" id="edit-longitude" name="longitude" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">สื่อปัจจุบัน</label>
                <div id="current-media-container" class="flex flex-wrap gap-2 mb-2">
                    </div>
                <label class="block text-gray-700 font-semibold mb-2">เพิ่มสื่อใหม่</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <input type="file" id="edit_image_file" name="image_file[]" class="w-full text-gray-700" multiple>
                    <input type="file" id="edit_video_file" name="video_file[]" class="w-full text-gray-700" multiple>
                    <input type="file" id="edit_audio_file" name="audio_file[]" class="w-full text-gray-700" multiple>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-500 text-white px-6 py-2 rounded-md font-semibold hover:bg-indigo-600 transition-colors">บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>
</div>

<div id="add-category-modal" class="modal hidden">
    <div class="modal-content">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 class="text-2xl font-bold text-purple-600">เพิ่มหมวดหมู่ใหม่</h3>
            <span class="text-gray-500 text-2xl cursor-pointer" onclick="closeAddCategoryModal()">&times;</span>
        </div>
        <form action="dashboard.php" method="POST">
            <input type="hidden" name="action" value="add_category">
            <div class="mb-4">
                <label for="category_name" class="block text-gray-700 font-semibold mb-2">ชื่อหมวดหมู่</label>
                <input type="text" id="category_name" name="category_name" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-purple-500 text-white px-6 py-2 rounded-md font-semibold hover:bg-purple-600 transition-colors">บันทึกหมวดหมู่</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTab(tabId, element) {
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.remove('active');
        });
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');
        element.classList.add('active');
    }

    const editModal = document.getElementById('edit-modal');
    function openEditModal(location) {
        document.getElementById('edit-location-id').value = location.id;
        document.getElementById('edit-name').value = location.name;
        document.getElementById('edit-description').value = location.description;
        document.getElementById('edit-category-id').value = location.category_id;
        document.getElementById('edit-latitude').value = location.latitude;
        document.getElementById('edit-longitude').value = location.longitude;
        
        // Dynamically load current media for editing
        const mediaContainer = document.getElementById('current-media-container');
        mediaContainer.innerHTML = '';
        location.media.forEach(media => {
            let mediaElement;
            if (media.media_type === 'image') {
                mediaElement = `<div class="relative group"><img src="../${media.url}" class="w-24 h-24 object-cover rounded-md" alt="รูปภาพ"><a href="dashboard.php?action=delete_media&id=${media.id}" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบรูปภาพนี้?');"><i class="fas fa-times text-xs"></i></a></div>`;
            } else if (media.media_type === 'video') {
                mediaElement = `<div class="relative group"><video src="../${media.url}" class="w-24 h-24 object-cover rounded-md" controls></video><a href="dashboard.php?action=delete_media&id=${media.id}" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบวิดีโอนี้?');"><i class="fas fa-times text-xs"></i></a></div>`;
            } else if (media.media_type === 'audio') {
                mediaElement = `<div class="relative group"><audio src="../${media.url}" class="w-24 h-12" controls></audio><a href="dashboard.php?action=delete_media&id=${media.id}" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบเสียงนี้?');"><i class="fas fa-times text-xs"></i></a></div>`;
            }
            mediaContainer.innerHTML += mediaElement;
        });
        
        editModal.classList.remove('hidden');
    }

    function closeEditModal() {
        editModal.classList.add('hidden');
    }
    
    // New functions for Detail Modal
    const detailModal = document.getElementById('detail-modal');
    function openDetailModal(location) {
        document.getElementById('detail-name').textContent = location.name;
        document.getElementById('detail-category').textContent = location.category_name;
        document.getElementById('detail-description').textContent = location.description;
        document.getElementById('detail-latitude').textContent = location.latitude;
        document.getElementById('detail-longitude').textContent = location.longitude;

        const mediaContainer = document.getElementById('detail-media-container');
        mediaContainer.innerHTML = '';
        location.media.forEach(media => {
            let mediaElement;
            if (media.media_type === 'image') {
                mediaElement = `<img src="../${media.url}" class="w-full h-auto rounded-md media-grid-item" alt="รูปภาพ">`;
            } else if (media.media_type === 'video') {
                mediaElement = `<video src="../${media.url}" class="w-full h-auto rounded-md media-grid-item" controls></video>`;
            } else if (media.media_type === 'audio') {
                mediaElement = `<audio src="../${media.url}" class="w-full h-12" controls></audio>`;
            }
            mediaContainer.innerHTML += mediaElement;
        });

        detailModal.classList.remove('hidden');
    }
    function closeDetailModal() {
        detailModal.classList.add('hidden');
    }

    const addModal = document.getElementById('add-location-modal');
    function openAddModal() {
        addModal.classList.remove('hidden');
    }

    function closeAddModal() {
        addModal.classList.add('hidden');
    }

    const addCategoryModal = document.getElementById('add-category-modal');
    function openAddCategoryModal() {
        addCategoryModal.classList.remove('hidden');
    }

    function closeAddCategoryModal() {
        addCategoryModal.classList.add('hidden');
    }

    window.onclick = function(event) {
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == addModal) {
            closeAddModal();
        }
        if (event.target == addCategoryModal) {
            closeAddCategoryModal();
        }
        if (event.target == detailModal) {
            closeDetailModal();
        }
    }
</script>
</body>
</html>