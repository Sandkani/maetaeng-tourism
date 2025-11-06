<?php
session_start();
include '../config/config.php';

// ตรวจสอบว่าผู้ใช้เป็น Admin และล็อกอินแล้ว
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$message = '';
$admin_id = $_SESSION['admin_id'];

// Handle form submission for updating profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_profile') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $bio = $_POST['bio'] ?? '';

        $profile_pic_url = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = uniqid() . '-' . basename($_FILES['profile_pic']['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $file_path)) {
                $profile_pic_url = 'admin/uploads/' . $file_name;
            } else {
                $message = "อัปโหลดรูปภาพไม่สำเร็จ.";
            }
        }

        $stmt_fetch = $conn->prepare("SELECT name, email, bio, profile_pic FROM admins WHERE id = ?");
        $stmt_fetch->bind_param("i", $admin_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        $current_admin = $result->fetch_assoc();
        $stmt_fetch->close();

        $name = $name ?: $current_admin['name'];
        $email = $email ?: $current_admin['email'];
        $bio = $bio ?: $current_admin['bio'];
        $profile_pic_url = $profile_pic_url ?: $current_admin['profile_pic'];

        $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, bio = ?, profile_pic = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssssi", $name, $email, $bio, $profile_pic_url, $admin_id);
            if ($stmt->execute()) {
                $message = "อัปเดตข้อมูลส่วนตัวสำเร็จ 🎉";
            } else {
                $message = "อัปเดตข้อมูลไม่สำเร็จ: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Error preparing statement: " . $conn->error;
        }
    }
    $_SESSION['message'] = $message;
    header("Location: profile.php");
    exit();
}

$admin_data = null;
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ดูแลระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { font-family: 'Kanit', sans-serif; }

        /* Custom Styles for 3D and Glow Effect */
        .card-effect {
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .card-effect:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .hover-3d-effect {
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .hover-3d-effect:hover > * {
            transform: translateZ(20px);
        }

        .glow-border {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem; /* rounded-lg */
            background-color: #f9fafb; /* bg-gray-50 */
            z-index: 1;
        }

        .glow-border::before,
        .glow-border::after {
            content: '';
            position: absolute;
            top: -100px;
            left: -100px;
            width: 300%;
            height: 300%;
            background: conic-gradient(from 0deg, #ffc107, #007bff, #ffc107, #007bff);
            animation: rotate-glow 5s linear infinite;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        
        .glow-border::after {
            background: conic-gradient(from 180deg, #ffc107, #007bff, #ffc107, #007bff);
            animation: rotate-glow 5s linear infinite reverse;
        }

        .glow-border:hover::before,
        .glow-border:hover::after {
            opacity: 1;
        }
        
        @keyframes rotate-glow {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 p-8" style="background-color: #1a202c;">
    <header class="bg-emerald-600 text-white p-6 shadow-md rounded-xl mb-8">
        <div class="container mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="text-2xl font-bold tracking-wide transition-transform hover:scale-105">
                <i class="fas fa-chart-line mr-2"></i> แดชบอร์ดผู้ดูแลระบบ
            </a>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-emerald-600 py-2 px-4 rounded-full font-bold hover:bg-gray-200 transition-colors shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i> ย้อนกลับ
                </a>
                <a href="logout.php" class="bg-white text-emerald-600 py-2 px-4 rounded-full font-bold hover:bg-gray-200 transition-colors shadow-md">
                    ออกจากระบบ <i class="fas fa-sign-out-alt ml-2"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="container mx-auto bg-gray-800 p-8 rounded-lg shadow-lg hover-3d-effect">
        <h2 class="text-3xl font-bold text-emerald-400 mb-6 border-b-2 border-emerald-500 pb-2">โปรไฟล์ผู้ดูแลระบบ</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-emerald-700 border border-emerald-400 text-emerald-100 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['message']); ?></span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="col-span-1 glow-border card-effect p-6 flex flex-col items-center justify-center text-center">
                <img src="../<?php echo htmlspecialchars($admin_data['profile_pic'] ?? 'placeholders/default-profile.png'); ?>" 
                     alt="รูปโปรไฟล์" 
                     class="w-32 h-32 rounded-full object-cover mb-4 border-4 border-gray-700 shadow-lg transition-transform duration-300 transform hover:scale-110">
                <h3 class="text-xl font-bold text-gray-100"><?php echo htmlspecialchars($admin_data['name'] ?? 'ไม่มีชื่อ'); ?></h3>
                <p class="text-gray-400 mb-2">บทบาท: ผู้ดูแลระบบ</p>
                <p class="text-gray-300 italic">"<?php echo htmlspecialchars($admin_data['bio'] ?? 'ยังไม่มีคำอธิบาย'); ?>"</p>
            </div>

            <div class="col-span-2 glow-border card-effect p-6 border-l-4 border-emerald-500">
                <h3 class="text-2xl font-bold text-gray-100 mb-4">แก้ไขข้อมูลส่วนตัว</h3>
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-4">
                        <label for="name" class="block text-gray-300 font-medium mb-2">ชื่อ</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_data['name'] ?? ''); ?>" 
                               class="w-full p-3 border border-gray-600 bg-gray-700 text-white rounded-md focus:ring-2 focus:ring-emerald-400 transition-all duration-300">
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-300 font-medium mb-2">อีเมล</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" 
                               class="w-full p-3 border border-gray-600 bg-gray-700 text-white rounded-md focus:ring-2 focus:ring-emerald-400 transition-all duration-300">
                    </div>
                    <div class="mb-4">
                        <label for="bio" class="block text-gray-300 font-medium mb-2">คำอธิบายสั้นๆ (Bio)</label>
                        <textarea id="bio" name="bio" rows="4" 
                                  class="w-full p-3 border border-gray-600 bg-gray-700 text-white rounded-md focus:ring-2 focus:ring-emerald-400 transition-all duration-300"><?php echo htmlspecialchars($admin_data['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-6">
                        <label for="profile_pic" class="block text-gray-300 font-medium mb-2">อัปโหลดรูปโปรไฟล์ใหม่</label>
                        <input type="file" id="profile_pic" name="profile_pic" 
                               class="w-full p-3 border border-gray-600 bg-gray-700 text-white rounded-md">
                    </div>
                    
                    <div class="text-right">
                        <button type="submit" class="bg-emerald-600 text-white font-bold py-3 px-8 rounded-full shadow-lg hover:bg-emerald-700 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>