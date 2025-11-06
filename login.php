<?php
session_start();
// ป้องกันการแคชของเบราว์เซอร์
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// รวมไฟล์เชื่อมต่อฐานข้อมูล
include '../config/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $sql = "SELECT userID, username, password FROM User WHERE email = ?";
    
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['userID'];
                $_SESSION['username'] = $user['username'];
                header("Location: ../public/index.php");
                exit();
            } else {
                $message = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $message = "ไม่พบผู้ใช้ด้วยอีเมลนี้";
        }

        $stmt->close();
    } catch (Exception $e) {
        $message = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage();
    }

    if (isset($conn)) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ใช้งาน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- ใช้ Kanit font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>
<body class="font-kanit bg-emerald-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-sm">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">เข้าสู่ระบบ 🔑</h1>
        <?php if ($message): ?>
            <!-- แสดงข้อความแจ้งเตือน (รหัสผ่านไม่ถูกต้อง, ไม่พบผู้ใช้) -->
            <div class="<?php echo strpos($message, 'ไม่ถูกต้อง') !== false || strpos($message, 'ไม่พบผู้ใช้') !== false ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?> p-3 rounded-md mb-4 text-center">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">อีเมล:</label>
                <input type="email" id="email" name="email" required
                        class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-shadow">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required
                        class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-shadow">
            </div>
            <div class="flex items-center justify-between">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl focus:outline-none focus:shadow-outline w-full transition-colors">
                    เข้าสู่ระบบ
                </button>
            </div>
        </form>
        
        <!-- *** ลิงก์ที่แก้ไขถูกต้องแล้ว: "สมัครสมาชิก" *** -->
       <p class="text-center text-gray-500 text-sm mt-4">
    ยังไม่มีบัญชี? <a href="register.php" class="text-indigo-500 hover:underline">สมัครสมาชิก</a>
</p>
    </div>
</body>
</html>
