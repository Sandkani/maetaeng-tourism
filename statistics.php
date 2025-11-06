<?php
// เริ่ม session
session_start();

// --- การเชื่อมต่อฐานข้อมูลจริง ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "maetaeng_tourism"; // ชื่อฐานข้อมูลของคุณ

$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// 1. ดึงข้อมูลสถานที่ยอดนิยม (Top 3) ตามยอดเข้าชม
// ตรวจสอบว่าตาราง location ของคุณชื่อ location1 หรือ location หากเกิด Unknown table 'location1' ให้เปลี่ยนเป็น 'location'
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

// 2. ดึงข้อมูลสถานที่ทั้งหมด เรียงตามยอดเข้าชมจากมากไปน้อย
$all_locations = [];
$sql_all = "SELECT 
              l.id,
              l.name,
              s.viewCount,
              (SELECT url FROM media WHERE location_id = l.id AND media_type = 'image' LIMIT 1) AS image_url
            FROM statistic s
            JOIN location1 l ON s.locationID = l.id
            ORDER BY s.viewCount DESC";

$result_all = $conn->query($sql_all);
if ($result_all) {
    while ($row = $result_all->fetch_assoc()) {
        $all_locations[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติความนิยม - เที่ยวแม่แตง</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@100;400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; }
        /* Custom styles for the 'World-Class Programmer' look */
        .rank-badge {
            clip-path: polygon(0% 0%, 100% 0%, 100% 75%, 50% 100%, 0% 75%);
            width: 5rem;
            height: 5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: #8B4513; }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A9A9A9); color: #36454F; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); color: #FEF3C7; }
        .stat-card:hover .rank-number {
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body class="font-kanit bg-gray-900 p-4 min-h-screen flex flex-col text-gray-100">
    <!-- แก้ไข: ลบ transform skew-y-[-1deg] ออกเพื่อให้ส่วนหัวไม่เอียง -->
    <header class="bg-indigo-900 text-white p-6 shadow-2xl rounded-xl relative overflow-hidden -mt-2">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-800 to-purple-900 opacity-90 transition-opacity duration-500 hover:opacity-100"></div>
        <!-- แก้ไข: ลบ skew-y-[1deg] ออกเพื่อให้เนื้อหาในส่วนหัวไม่เอียง -->
        <div class="container mx-auto flex flex-col sm:flex-row justify-between items-center relative z-10">
            
            <!-- เปลี่ยนจาก <a> เป็น <div> เพื่อทำให้ส่วนโลโก้และชื่อเว็บไซต์ไม่สามารถกดได้ -->
            <div class="flex items-center text-4xl font-extrabold mb-4 sm:mb-0 tracking-wider">
                <img src="assets/images/logo.png" 
                      alt="เที่ยวแม่แตง Logo" 
                      class="h-12 w-12 mr-4 rounded-full shadow-lg" 
                      onerror="this.onerror=null;this.src='https://placehold.co/150x48/4F46E5/FFFFFF?text=Logo+Error';">
                <span class="text-white text-4xl font-extrabold">เที่ยวแม่แตง</span>
                <span class="text-yellow-400 text-sm italic font-light ml-2">PRO</span>
            </div>
            <!-- ปุ่ม 'กลับหน้าหลัก' ยังคงทำงานเหมือนเดิม -->
            <nav>
                <a href="index.php" class="bg-yellow-400 text-gray-900 py-2 px-6 rounded-full font-bold hover:bg-yellow-300 transition-all duration-300 transform hover:translate-y-[-2px] shadow-lg">กลับหน้าหลัก</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto mt-12 p-4 flex-grow">
        <h1 class="text-5xl font-extrabold text-center mb-12 text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 animate-pulse">สถิติความนิยม</h1>

        <section class="mb-20">
            <h2 class="text-3xl font-bold text-center text-gray-300 mb-10">🏆 สถานที่ยอดนิยม (Top 3 Performance)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php if (!empty($top_locations)): ?>
                    <?php foreach ($top_locations as $index => $location): ?>
                        <?php 
                        // --- โค้ด Path รูปภาพของสถานที่ ---
                        $image_url_from_db = $location['image_url'];
                        // สมมติว่าไฟล์นี้อยู่ใน public/ และรูปภาพอยู่ใน admin/uploads/image.jpg
                        // ต้องถอย 1 ระดับ (../) เพื่อไปหาไฟล์จาก root project
                        $display_image_url = '../' . $image_url_from_db;
                        
                        // ใช้ URL สำรองถ้าไม่มีภาพ
                        $final_image_url = htmlspecialchars($display_image_url ?: 'https://placehold.co/400x250/1F2937/F9FAFB?text=NO+IMAGE+DATA');
                        // ------------------------------------

                        $rank = $index + 1;
                        $rank_class = '';
                        if ($rank === 1) $rank_class = 'rank-1 shadow-2xl shadow-yellow-500/50';
                        if ($rank === 2) $rank_class = 'rank-2 shadow-xl shadow-gray-500/50';
                        if ($rank === 3) $rank_class = 'rank-3 shadow-lg shadow-amber-800/50';
                        ?>
                       <a href="location_detail_controller.php?id=<?php echo htmlspecialchars($location['id']); ?>" 
                          class="stat-card relative block rounded-2xl overflow-hidden shadow-2xl border-4 border-transparent hover:border-indigo-400 transform transition-all duration-500 hover:scale-[1.03] bg-gray-800/70 backdrop-blur-sm">
                            
                            <!-- Image container with fixed height -->
                            <div class="h-64">
                                <img src="<?php echo $final_image_url; ?>" 
                                      alt="<?php echo htmlspecialchars($location['name']); ?>" 
                                      class="w-full h-full object-cover transition-opacity duration-500 hover:opacity-80"
                                      onerror="this.onerror=null;this.src='https://placehold.co/400x250/1F2937/F9FAFB?text=NO+IMAGE+FOUND';">
                            </div>

                            <!-- Rank Badge -->
                            <div class="absolute top-[-0.5rem] right-[-0.5rem] p-4 z-30">
                                <div class="rank-badge <?php echo $rank_class; ?> transform rotate-12">
                                    <span class="text-4xl font-extrabold rank-number drop-shadow-lg">#<?php echo $rank; ?></span>
                                </div>
                            </div>
                            
                            <!-- Content Overlay -->
                            <div class="p-6">
                                <h3 class="text-2xl font-bold text-indigo-300 mb-2"><?php echo htmlspecialchars($location['name']); ?></h3>
                                <div class="flex items-center justify-between text-lg text-gray-300">
                                    <span>ยอดเข้าชม:</span>
                                    <span class="font-extrabold text-yellow-400 text-2xl"><?php echo number_format($location['viewCount']); ?></span>
                                </div>
                                <div class="mt-4 text-center">
                                    <span class="inline-block bg-indigo-600 text-white text-sm font-semibold py-1 px-3 rounded-full hover:bg-indigo-500 transition-colors">ดูรายละเอียด</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="md:col-span-3 text-center text-gray-500 italic p-10 bg-gray-800 rounded-xl">
                        <p class="text-xl">⚠️ ไม่พบข้อมูลสถิติสถานที่ยอดนิยม</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <h2 class="text-3xl font-bold text-center text-gray-300 mb-6">📊 สถิติยอดเข้าชมทั้งหมด (Ranked Listing)</h2>
            <div class="bg-gray-800 rounded-xl shadow-2xl overflow-hidden border border-gray-700">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-700 to-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-sm font-extrabold text-indigo-300 uppercase tracking-wider w-1/12">
                                อันดับ
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-sm font-extrabold text-indigo-300 uppercase tracking-wider">
                                ชื่อสถานที่
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-sm font-extrabold text-indigo-300 uppercase tracking-wider w-1/4">
                                ยอดเข้าชม
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-900 divide-y divide-gray-700/50">
                        <?php if (!empty($all_locations)): ?>
                            <?php foreach ($all_locations as $index => $location): ?>
                                <tr class="hover:bg-gray-700/50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-yellow-400">
                                        #<?php echo $index + 1; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-base text-gray-300">
                                        <a href="location_detail_controller.php?id=<?php echo htmlspecialchars($location['id']); ?>" class="text-indigo-400 hover:text-indigo-300 hover:underline transition-colors">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-lg font-extrabold text-right text-green-400 tracking-wider">
                                        <?php echo number_format($location['viewCount']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-base text-gray-500 italic">
                                    ไม่พบข้อมูลสถิติในขณะนี้
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="bg-indigo-900/70 backdrop-blur-sm text-gray-400 p-6 text-center mt-12 rounded-xl shadow-inner shadow-indigo-900/50 border-t border-indigo-700">
        <p class="font-light tracking-wide">© 2024 เที่ยวแม่แตง - Powered by Global Programmer Architecture</p>
    </footer>
</body>
</html>
