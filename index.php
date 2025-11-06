<?php
// PHP: ส่วนการจัดการสถานะและข้อมูล (Data Handling Section)
//-------------------------------------------------------------

// เริ่ม session เพื่อจัดการสถานะการล็อกอิน
session_start();
if (!isset($_SESSION['username'])) {
    // Redirect ไปหน้า login.php ถ้ายังไม่ได้ล็อกอิน
    header("Location: login.php");
    exit();
}

// PHP: ส่วนจำลองฐานข้อมูล (Mock Database Simulation)
// โค้ดส่วนนี้ใช้สำหรับการจำลองข้อมูล โดยไม่ต้องมีฐานข้อมูลจริง
//----------------------------------------------
class MockDatabase {
    public function query($sql) {
        if (strpos($sql, 'FROM categories') !== false) {
            return new class {
                public function fetch_all($mode) {
                    // ข้อมูลหมวดหมู่จำลอง (เพิ่มหมวดหมู่ใหม่: 'จุดชมวิว')
                    return [
                        ['id' => 1, 'name' => 'วัด'],
                        ['id' => 2, 'name' => 'ธรรมชาติ'],
                        ['id' => 3, 'name' => 'กิจกรรม'],
                        ['id' => 4, 'name' => 'แหล่งท่องเที่ยว'],
                        // 🌟 เพิ่มหมวดหมู่ใหม่สำหรับ 'ไฮ่เขากอด'
                        ['id' => 5, 'name' => 'จุดชมวิว']
                    ];
                }
            };
        }
        return false;
    }
    public function close() {}
}

$conn = new MockDatabase();
//----------------------------------------------

// ดึงข้อมูลหมวดหมู่ทั้งหมด
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories) {
    // ดึงข้อมูลจากฐานข้อมูลจำลอง
    $categories = $result_categories->fetch_all(MYSQLI_ASSOC);
}

// ตรวจสอบตัวกรองและโหมดการแสดงผล
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$is_map_view = isset($_GET['view']) && $_GET['view'] === 'map';
$locations = [];

// ข้อมูลสถานที่ท่องเที่ยวจำลอง (ปรับปรุง category_id และ category_name)
// **ปรับปรุง: แก้ไข 'image_url' ให้ชี้ไปที่ไฟล์ภายในเซิร์ฟเวอร์ของคุณ**
$locations_list = [
    [
        'id' => 1,
        'name' => 'อุทยานแห่งชาติน้ำตกบัวตอง-น้ำพุเจ็ดสี',
        'description' => 'น้ำตกที่สวยงามและน้ำพุที่น่าสนใจ',
        'category_name' => 'ธรรมชาติ',
        'total_views' => '850',
        'icon' => '⛰️',
        'coords' => ['top' => '45%', 'left' => '75%'],
        'category_id' => 2,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_1.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2480.jpg'
    ],
    [
        'id' => 2,
        'name' => 'วัดเด่นสะหลีศรีเมืองแก่น',
        'description' => 'วัดที่สวยงามและมีสถาปัตยกรรมที่โดดเด่น',
        'category_name' => 'วัด',
        'total_views' => '999',
        'icon' => '🙏',
        'coords' => ['top' => '15%', 'left' => '30%'],
        'category_id' => 1,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_2.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2261.jpg'
    ],
    [
        'id' => 3,
        'name' => 'เขื่อนแม่งัดสมบรูณ์ชล',
        'description' => 'เขื่อนขนาดใหญ่ที่ล้อมรอบด้วยภูเขาและธรรมชาติ',
        'category_name' => 'ธรรมชาติ',
        'total_views' => '780',
        'icon' => '🏞️',
        'coords' => ['top' => '60%', 'left' => '40%'],
        'category_id' => 2,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_3.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2420.jpg'
    ],
    [
        'id' => 4,
        'name' => 'ปางช้างแม่แตง',
        'description' => 'สถานที่สำหรับชมและทำกิจกรรมกับช้าง',
        'category_name' => 'กิจกรรม',
        'total_views' => '620',
        'icon' => '🐘',
        'coords' => ['top' => '25%', 'left' => '60%'],
        'category_id' => 3,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_4.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2491.jpg'
    ],
    [
        'id' => 5,
        'name' => 'แดนเทวดา',
        'description' => 'คาเฟ่และร้านอาหารที่มีการตกแต่งสวยงาม',
        'category_name' => 'แหล่งท่องเที่ยว',
        'total_views' => '540',
        'icon' => '☕',
        'coords' => ['top' => '70%', 'left' => '20%'],
        'category_id' => 4,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_5.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images//DSCF2326.jpg'
    ],
    [
        'id' => 6,
        'name' => 'สวนสนแม่แตง',
        'description' => 'สวนป่าสนที่ร่มรื่นและเย็นสบาย',
        'category_name' => 'ธรรมชาติ',
        'total_views' => '450',
        'icon' => '🌲',
        'coords' => ['top' => '85%', 'left' => '55%'],
        'category_id' => 2,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_6.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2302.jpg'
    ],
    [
        'id' => 7,
        'name' => 'น้ำตกหมอกฟ้า',
        'description' => 'น้ำตกที่สวยงามอีกแห่งหนึ่งของแม่แตง',
        'category_name' => 'ธรรมชาติ',
        'total_views' => '390',
        'icon' => '🌊',
        'coords' => ['top' => '50%', 'left' => '55%'],
        'category_id' => 2,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_7.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2471.jpg'
    ],
    [
        'id' => 8,
        'name' => 'น้ำพุร้อนโป่งเดือด',
        'description' => 'น้ำพุร้อนธรรมชาติที่สามารถแช่เท้าได้',
        'category_name' => 'ธรรมชาติ',
        'total_views' => '280',
        'icon' => '♨️',
        'coords' => ['top' => '75%', 'left' => '85%'],
        'category_id' => 2,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_8.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2582.jpg'
    ],
    [
        'id' => 9,
        'name' => 'ปางเปาบีช',
        'description' => 'ชายหาดจำลองริมเขื่อนแม่งัด',
        'category_name' => 'ธรรมชาติ',
        'total_views' => '150',
        'icon' => '🏖️',
        'coords' => ['top' => '80%', 'left' => '10%'],
        'category_id' => 2,
        // **ลิงก์ภายใน: สมมติว่าไฟล์ชื่อ location_9.jpg อยู่ใน admin/uploads/images/**
        'image_url' => 'assets/images/DSCF2397.jpg'
    ],
    // 🌟 ส่วนที่เพิ่มใหม่: สถานที่หมายเลข 10 - ไฮ่เขากอด
    [
        'id' => 10,
        'name' => 'ไฮ่เขากอด',
        'description' => 'จุดชมวิวทิวทัศน์ภูเขาแบบพาโนรามาและที่พักวิวสวย',
        'category_name' => 'จุดชมวิว',
        'total_views' => '920',
        'icon' => '🌅',
        'coords' => ['top' => '10%', 'left' => '50%'], // ตำแหน่งบนแผนที่จำลอง
        'category_id' => 5, // ใช้ Category ID ที่เพิ่มใหม่
        'image_url' => 'assets/images/DSCF2542.jpg' // เปลี่ยนเป็นรูปภาพที่เหมาะสม
    ]
];
// กรองข้อมูลตามหมวดหมู่ที่เลือก
if ($category_id > 0) {
    $locations = array_filter($locations_list, function($location) use ($category_id) {
        return $location['category_id'] === $category_id;
    });
} else {
    $locations = $locations_list;
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_map_view ? 'แผนที่ท่องเที่ยวแม่แตง' : 'สถานที่ท่องเที่ยวแม่แตง'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* กำหนดฟอนต์หลักสำหรับทั้ง body */
        body {
            font-family: 'Kanit', sans-serif;
        }
        .container {
            max-width: 1024px;
        }
        
        /* สไตล์สำหรับ Map View */
        .map-container {
            position: relative;
            /* เพิ่มพื้นหลังเป็นลายจุดอ่อนๆ เพื่อให้ดูเหมือนพื้นผิวแผนที่ */
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="10" cy="10" r="1" fill="%23d1c4e9"/><circle cx="50" cy="30" r="1" fill="%23d1c4e9"/><circle cx="90" cy="50" r="1" fill="%23d1c4e9"/><circle cx="20" cy="70" r="1" fill="%23d1c4e9"/><circle cx="60" cy="90" r="1" fill="%23d1c4e9"/></svg>');
            background-size: 20px 20px;
            background-color: #f3f4f6;
            background-repeat: repeat;
        }
        .landmark {
            position: absolute;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); /* เอฟเฟกต์เคลื่อนไหวที่นุ่มนวล */
            z-index: 5;
        }
        /* เอฟเฟกต์ Hover สำหรับจุดบนแผนที่ (Map Landmark Hover Effect) */
        .landmark:hover {
            transform: scale(1.2) translateY(-10px); /* ขยายและยกขึ้นมากขึ้น */
            filter: drop-shadow(0 0 15px #4f46e5); /* เพิ่มเงาสีน้ำเงิน */
            z-index: 100;
        }
        .landmark-icon {
            font-size: 3rem;
            line-height: 1;
        }
        .landmark-name {
            background-color: #ffffff;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-top: 0.5rem;
            font-weight: bold;
            white-space: nowrap;
        }
        /* สไตล์สำหรับกล่องข้อมูลที่ปรากฏเมื่อ Hover บนแผนที่ */
        .info-box {
            position: absolute;
            background-color: #ffffff;
            padding: 1rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-width: 250px;
            transform: translate(-50%, 0);
            left: 50%;
            bottom: 100%;
            margin-bottom: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s, transform 0.3s;
        }
        .landmark:hover .info-box {
            opacity: 1;
            visibility: visible;
        }
        .info-box::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%) rotate(45deg);
            width: 16px;
            height: 16px;
            background-color: #ffffff;
        }
        
        /* สไตล์สำหรับ List View Card - ส่วนสำคัญที่ได้รับการปรับปรุงตามคำขอ 3b */
        /* Custom CSS: เอฟเฟกต์ยกตัวและแสงเรือง 3 มิติ (3D Lift and Blue/Gold Glow Effect) */
        .location-card-3d {
            transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1); /* ใช้ Cubic Bezier เพื่อให้การเคลื่อนไหวดูนุ่มนวลระดับมืออาชีพ */
            border: 2px solid rgba(99, 102, 241, 0.2); /* ขอบสีม่วงอ่อนเริ่มต้น */
            background-color: #ffffff; /* เปลี่ยนเป็นสีขาวเพื่อเน้นแสงเรือง */
        }

        .location-card-3d:hover {
            transform: translateY(-10px) scale(1.03); /* ยกตัวขึ้นและขยายเล็กน้อย */
            /* สร้างแสงเรือง 3 มิติ: แสงสีน้ำเงิน (หลัก) และแสงสีทอง/เหลือง (รอง) */
            box-shadow: 
                0 25px 50px -12px rgba(79, 70, 229, 0.8), /* แสงเงาสีน้ำเงินเข้ม */
                0 0 20px 8px rgba(251, 191, 36, 0.7), /* แสงเรืองสีทอง/เหลือง */
                0 0 5px 1px rgba(255, 255, 255, 0.8) inset; /* แสงสะท้อนสีขาวด้านใน */
            border-color: #fcd34d; /* เปลี่ยนขอบเป็นสีทองเมื่อโฮเวอร์ */
        }

    </style>
</head>
<body class="font-kanit bg-gray-100 p-4 min-h-screen">
    <header class="bg-indigo-600 text-white p-6 shadow-2xl rounded-xl relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-700 to-purple-700 opacity-90"></div>
        <div class="container mx-auto flex justify-between items-center relative z-10">
            <a href="index.php" class="text-3xl font-bold hover:text-yellow-300 transition-colors">🏞️ เที่ยวแม่แตง</a>
            <nav class="flex items-center space-x-4">
                <a href="statistics.php" class="bg-white text-indigo-600 py-2 px-4 rounded-full font-bold hover:bg-gray-200 transition-colors shadow-md">สถิติความนิยม</a>
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="font-bold hidden md:inline text-yellow-300">ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="bg-red-500 text-white py-2 px-4 rounded-full font-bold hover:bg-red-600 transition-colors shadow-md">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="logout.php" class="bg-green-500 text-white py-2 px-4 rounded-full font-bold hover:bg-green-600 transition-colors shadow-md">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <main class="container mx-auto mt-8 p-6 bg-white rounded-3xl shadow-2xl">
        <?php if ($is_map_view): ?>
            <h1 class="text-4xl font-bold text-center text-gray-800 mb-2">🗺️ แผนที่ท่องเที่ยวแม่แตง</h1>
            <p class="text-center text-gray-500 mb-8">สำรวจสถานที่ยอดนิยมและแหล่งท่องเที่ยวที่น่าสนใจ</p>
            
            <div class="w-full text-center mb-6">
                <a href="index.php" class="inline-block bg-indigo-600 text-white font-bold py-3 px-8 rounded-full shadow-xl hover:bg-indigo-700 transition-colors transform hover:scale-105">
                    <span class="mr-2">📋</span> กลับสู่มุมมองรายการ
                </a>
            </div>
            
            <div class="map-container w-full h-[600px] rounded-2xl overflow-hidden p-8 relative shadow-inner border border-gray-200">
                <?php foreach ($locations as $location): ?>
                    <div class="landmark group" style="top: <?php echo htmlspecialchars($location['coords']['top']); ?>; left: <?php echo htmlspecialchars($location['coords']['left']); ?>;">
                        <a href="location_detail_controller.php?id=<?php echo htmlspecialchars($location['id']); ?>" class="block">
                            <div class="landmark-icon"><?php echo htmlspecialchars($location['icon']); ?></div>
                            <div class="landmark-name text-sm"><?php echo htmlspecialchars($location['name']); ?></div>
                            <div class="info-box group-hover:block">
                                <img src="<?php echo htmlspecialchars($location['image_url']); ?>" alt="<?php echo htmlspecialchars($location['name']); ?>" class="rounded-lg mb-2 object-cover w-full h-24">
                                <h3 class="font-bold text-base mb-1 text-indigo-700"><?php echo htmlspecialchars($location['name']); ?></h3>
                                <p class="text-xs text-gray-600 line-clamp-2"><?php echo htmlspecialchars($location['description']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <h1 class="text-5xl font-extrabold mb-2 text-center text-indigo-800">สำรวจสถานที่ท่องเที่ยวในแม่แตง! ⛰️</h1>
            <p class="text-center text-gray-600 mb-8 text-lg">ค้นหาสถานที่ที่คุณสนใจตามหมวดหมู่ที่คุณชื่นชอบ</p>
            
            <a href="index.php?view=map" class="block mb-8 hover:opacity-90 transition-opacity">
                <div class="w-full h-48 bg-indigo-500 rounded-2xl overflow-hidden relative shadow-lg hover:shadow-xl border-4 border-indigo-400">
                    <img src="https://placehold.co/1024x192/E5E7EB/5B21B6?text=Click+to+View+Map" alt="แผนที่แม่แตง" class="w-full h-full object-cover opacity-30 hover:opacity-10 transition-opacity duration-500">
                    <div class="absolute inset-0 flex items-center justify-center p-4">
                        <div class="bg-yellow-300/95 backdrop-blur-sm rounded-full px-8 py-4 font-black text-indigo-900 text-xl shadow-2xl transform hover:scale-105 transition-transform duration-500">
                            คลิกเพื่อดูในรูปแบบแผนที่จำลอง 🗺️
                        </div>
                    </div>
                </div>
            </a>
            
            <div class="flex flex-wrap justify-center gap-3 mb-10 p-4 bg-indigo-50 rounded-2xl shadow-inner border border-indigo-200">
                <a href="index.php" class="py-2 px-5 rounded-full text-base font-bold transition-all transform hover:scale-105
                    <?php echo $category_id === 0 ? 'bg-indigo-700 text-white shadow-xl ring-4 ring-indigo-300' : 'bg-white text-indigo-700 hover:bg-indigo-200 shadow-md'; ?>">
                    <span class="mr-1">✨</span>ทั้งหมด
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category_id=<?php echo htmlspecialchars($cat['id']); ?>" class="py-2 px-5 rounded-full text-base font-bold transition-all transform hover:scale-105
                        <?php echo $category_id === (int)$cat['id'] ? 'bg-indigo-700 text-white shadow-xl ring-4 ring-indigo-300' : 'bg-white text-indigo-700 hover:bg-indigo-200 shadow-md'; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (!empty($locations)): ?>
                    <?php foreach ($locations as $location): ?>
                        <a href="location_detail_controller.php?id=<?php echo htmlspecialchars($location['id'] ?? ''); ?>" class="location-card-3d block rounded-2xl shadow-xl relative overflow-hidden">
                            <div class="w-full h-52 bg-cover bg-center rounded-t-2xl" style="background-image: url('<?php echo htmlspecialchars($location['image_url']); ?>');">
                                <div class="w-full h-full bg-black/20 transition-all duration-500 group-hover:bg-black/0"></div>
                            </div>
                            <div class="p-6 relative z-10">
                                <h2 class="text-2xl font-black text-indigo-900 mb-1"><?php echo htmlspecialchars($location['name']); ?></h2>
                                <span class="bg-yellow-400 text-indigo-800 text-xs px-3 py-1 rounded-full inline-block font-bold shadow-md transition-colors duration-300"><?php echo htmlspecialchars($location['category_name'] ?? 'ทั่วไป'); ?></span>
                                <p class="text-gray-700 mt-3 line-clamp-3 text-sm"><?php echo htmlspecialchars($location['description']); ?></p>
                                <div class="mt-4 text-sm font-semibold text-gray-500 flex items-center justify-between">
                                    <span class="flex items-center text-indigo-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                        </span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center p-12 bg-indigo-50 rounded-xl border-4 border-dashed border-indigo-300">
                        <p class="text-xl text-indigo-600 font-bold">😭 ไม่พบสถานที่ท่องเที่ยวในหมวดหมู่นี้</p>
                        <p class="text-gray-500 mt-2">โปรดลองเลือกตัวกรองอื่น หรือดูรายการทั้งหมด</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="bg-gray-900 text-white p-6 text-center mt-12 rounded-t-3xl shadow-inner">
        <p class="text-sm">©2024 เที่ยวแม่แตง. สงวนลิขสิทธิ์ทั้งหมด | พัฒนาโดยKANOKPORN</p>
    </footer>
</body>
</html>