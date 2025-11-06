<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($location['name'] ?? 'สถานที่ท่องเที่ยว') ?></title>
    <link rel="stylesheet" href="assets/css/style.css"> 
</head>
<body>
<header class="header">
    <div class="logo">
        <img src="assets/images/logo.png" alt="Your Company Logo" class="site-logo">
    </div>
    <nav class="main-nav">
        <a href="index.php" id="home-btn-3b" class="nav-item">หน้าแรก</a>
    </nav>
</header>

<main class="container">
    <div class="back-link-wrapper">
        <a href="<?= htmlspecialchars($back_url ?? 'statistics.php') ?>" class="back-link">
            <span class="back-link-icon">←</span> กลับสู่หน้าสถิติ
        </a>
    </div>

    <?php if ($error_message): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php elseif ($location): ?>
        <section class="location-detail">
            <h1 class="location-title"><?= htmlspecialchars($location['name']) ?></h1>

            <div class="info-showcase">
                <div class="info-item">
                    <span class="info-label">ประเภท</span>
                    <span class="info-value"><?= htmlspecialchars($location['category_name'] ?? 'ไม่ระบุ') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">จำนวนผู้เข้าชม</span>
                    <span class="info-value visitors-count"><?= number_format($location['viewCount'] ?? 0) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">คะแนนเฉลี่ย</span>
                    <span class="info-value average-rating"><?= number_format($average_rating, 1) ?> ⭐</span>
                </div>
            </div>
            
            <h3 class="text-xl font-bold mb-2">ภาพสถานที่</h3>
            <?php if (!empty($images)): ?>
                <div class="carousel-container">
                    <div class="carousel-wrapper" id="carousel-wrapper">
                        <?php foreach($images as $img): ?>
                            <div class="carousel-item">
                                <img src="<?= htmlspecialchars($img) ?>" alt="รูปภาพสถานที่">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button class="carousel-btn" id="prev-btn">
                        <span class="back-link-icon">❮</span>
                    </button>
                    <button class="carousel-btn" id="next-btn">
                        <span class="back-link-icon">❯</span>
                    </button>

                    <div class="carousel-indicators" id="carousel-indicators">
                        </div>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 italic">ไม่พบภาพสถานที่</p>
            <?php endif; ?>

            <h3 class="text-xl font-bold mb-2 mt-8">รายละเอียด</h3>
            <p class="location-description"><?= nl2br(htmlspecialchars($location['description'] ?? '')) ?></p>
            
            <?php 
            if (!empty($audio_files)): 
            ?>
                <h3 class="text-xl font-bold mb-2 mt-8">เสียงบรรยาย</h3>
                <div class="audio-narration-group"> 
                <?php $i = 1; foreach($audio_files as $audio): ?>
                    <div class="audio-wrapper">
                        <button id="audio-play-btn-<?= $i ?>" 
                                class="audio-button audio-control-btn" 
                                data-audio-url="<?= htmlspecialchars($audio['url']) ?>">
                            <span class="audio-icon">▶</span>
                            <span id="audio-text-<?= $i ?>">ฟังเสียงบรรยาย (<?= htmlspecialchars($audio['lang_th'] ?? 'ไฟล์ที่ ' . $i) ?>)</span>
                        </button>

                        <audio id="audio-player-<?= $i ?>" style="display: none;">
                            <source src="<?= htmlspecialchars($audio['url']) ?>" type="audio/mpeg">
                            เบราว์เซอร์ของคุณไม่รองรับการเล่นไฟล์เสียง
                        </audio>
                    </div>
                <?php $i++; endforeach; ?>
                </div>
            <?php elseif ($audio_url): // รองรับกรณีที่ยังใช้ตัวแปรเก่า $audio_url สำหรับไฟล์เดียวอยู่ ?>
                <h3 class="text-xl font-bold mb-2 mt-8">เสียงบรรยาย</h3>
                <div class="audio-wrapper">
                    <button id="audio-play-btn" class="audio-button">
                        <span class="audio-icon">▶</span>
                        <span id="audio-text">ฟังเสียงบรรยาย</span>
                    </button>

                    <audio id="audio-player" style="display: none;">
                        <source src="<?= htmlspecialchars($audio_url) ?>" type="audio/mpeg">
                        เบราว์เซอร์ของคุณไม่รองรับการเล่นไฟล์เสียง
                    </audio>
                </div>
            <?php endif; ?> 
            <?php if (!empty($videos)): ?>
                <h3 class="text-xl font-bold mb-2 mt-8">วิดีโอแนะนำ</h3>
                <div class="video-gallery">
                    <?php foreach($videos as $vid): ?>
                        <div class="video-item">
                            <video controls>
                                <source src="<?= htmlspecialchars($vid) ?>" type="video/mp4">
                                เบราว์เซอร์ของคุณไม่รองรับการเล่นไฟล์วิดีโอ
                            </video>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3 class="text-xl font-bold mb-2 mt-8">ที่ตั้งบนแผนที่</h3>
            <?php if (isset($location['latitude']) && isset($location['longitude']) && $location['latitude'] && $location['longitude']): ?>
                <div class="map-container">
                    <iframe
                        width="100%"
                        height="100%"
                        frameborder="0"
                        style="border:0"
                        src="https://maps.google.com/maps?q=<?php echo urlencode($location['latitude']); ?>,<?php echo urlencode($location['longitude']); ?>&z=15&output=embed"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="text-center text-gray-700 mt-4">
                    <p><strong>พิกัด:</strong> <?php echo htmlspecialchars($location['latitude']); ?>, <?php echo htmlspecialchars($location['longitude']); ?></p>
                </div>
            <?php else: ?>
                <div class="text-center text-gray-500 italic mt-4">
                    **พิกัดไม่พร้อมใช้งาน**
                </div>
            <?php endif; ?>
            
            <section class="comments">
                <h2>ความคิดเห็น</h2>
                <form method="POST" class="comment-form">
                    <input type="hidden" name="location_id" value="<?= $id ?>">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <textarea name="comment" placeholder="แสดงความคิดเห็น (เป็นทางเลือก)"></textarea>
                        <div class="rating-input">
                            <span>คะแนน: </span>
                            <?php for($i=5; $i>=1; $i--): ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required><label for="star<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <button type="submit">ส่งความคิดเห็นและคะแนน</button>
                    <?php else: ?>
                        <p class="text-red-500">กรุณาเข้าสู่ระบบเพื่อแสดงความคิดเห็น</p>
                    <?php endif; ?>
                </form>

                <div class="comment-list">
                    <?php if (!empty($comments)): ?>
                        <?php foreach($comments as $c): ?>
                            <div class="comment-item">
                                <strong><?= htmlspecialchars($c['username']) ?></strong> 
                                (<span class="user-rating"><?= $c['rating'] ?? '-' ?></span> ⭐)
                                <p><?= htmlspecialchars($c['commentText']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 italic">ยังไม่มีความคิดเห็น</p>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    <?php endif; ?>
</main>

<script src="assets/js/script.js"></script>
</body>
</html>