<?php
session_start();
// ตรวจสอบว่าไฟล์ config อยู่ในตำแหน่งที่ถูกต้อง (สมมติว่า login.php อยู่ในโฟลเดอร์ admin และ config.php อยู่ในโฟลเดอร์หลัก)
include '../config/config.php'; 

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ป้องกัน XSS
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // เตรียมคำสั่ง SQL
    $sql = "SELECT id, password FROM admins WHERE name = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // ตรวจสอบรหัสผ่านที่ถูกแฮช
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                // เปลี่ยนเส้นทางไปยัง Dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
        $stmt->close();
    } else {
        // กรณีเกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL
        $message = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
    }
}
// ปิดการเชื่อมต่อ
$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ | EXCLUSIVE ACCESS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
        }
        /* พื้นหลัง Dark Mode สลับกับ Gradient */
        .login-bg {
            background-color: #0d1117; /* Dark background */
            background-image: radial-gradient(circle at 100% 100%, #1e293b 0%, #0d1117 50%);
        }
        /* สไตล์สำหรับกล่องฟอร์มที่ดูมีมิติ */
        .premium-card {
            background-color: #1f2937; /* Gray-800 Dark */
            border: 1px solid #374151; /* Gray-700 Border */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5), 0 30px 60px rgba(0, 0, 0, 0.7); /* Deep Shadow */
        }
        /* กำหนดสีฟิลด์ input เมื่อโฟกัส */
        input:focus {
            border-color: #fbbf24 !important; /* Amber-400 Gold */
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.4) !important; /* Custom Gold Focus Ring */
            background-color: #111827 !important; /* Darker input background */
            color: #f3f4f6; /* Light text */
        }
        /* สไตล์ไอคอนสีทอง */
        .icon-gold {
            color: #fbbf24;
        }
        /* ปุ่มเข้าสู่ระบบแบบ Gradient */
        .btn-gold-gradient {
            background-image: linear-gradient(to right, #fbbf24, #f59e0b, #d97706); /* Amber/Yellow Gradient */
            transition: all 0.3s ease;
        }
        .btn-gold-gradient:hover {
            box-shadow: 0 5px 15px rgba(251, 191, 36, 0.5);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="login-bg p-4 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full premium-card rounded-2xl p-10 transform transition-all duration-500">
        
        <div class="text-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 icon-gold mx-auto mb-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
            </svg>
            <h1 class="text-3xl font-extrabold text-gray-100 tracking-wider">SECURE ACCESS</h1>
            <p class="text-amber-400 font-semibold mt-2 border-b border-amber-500/50 pb-2 inline-block">PORTAL MANAGEMENT</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-red-900/50 border border-red-700 text-red-300 p-4 rounded-lg mb-6 text-center text-sm font-medium transition-opacity duration-300 ease-in opacity-100 shadow-md">
                <span class="font-bold">⚠️ ERROR:</span> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-6">
            
            <div>
                <label for="username" class="block text-sm font-semibold text-gray-300 mb-2">USERNAME:</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input type="text" id="username" name="username" required 
                           class="w-full py-3 pl-10 pr-4 border border-gray-600 bg-gray-900 rounded-lg focus:outline-none text-gray-200 shadow-inner transition duration-150 ease-in-out" 
                           placeholder="Enter Username">
                </div>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-300 mb-2">PASSWORD:</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input type="password" id="password" name="password" required 
                           class="w-full py-3 pl-10 pr-4 border border-gray-600 bg-gray-900 rounded-lg focus:outline-none text-gray-200 shadow-inner transition duration-150 ease-in-out"
                           placeholder="Enter Password">
                </div>
            </div>
            
            <button type="submit" 
                    class="w-full btn-gold-gradient text-gray-900 font-extrabold py-3 rounded-lg shadow-xl uppercase tracking-wider">
                LOGIN TO DASHBOARD
            </button>
        </form>
        
        <!-- <div class="mt-8 text-center">
            <a href="../index.php" class="text-sm text-gray-400 hover:text-amber-400 transition-colors duration-150 border-b border-dashed border-gray-600 hover:border-amber-400">
                ← Return to Public Site
            </a> -->
        </div>
    </div>
</body>
</html>