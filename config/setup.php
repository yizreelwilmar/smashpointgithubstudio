<?php
// config/setup.php
// Powershoot Badminton Management System Database Migration & Seeder

$config = require __DIR__ . '/database.php';

try {
    $dsnNoDb = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $pdoInit = new PDO($dsnNoDb, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $dbName = $config['database'];
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$dbName};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // 1. Community Settings Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `community_name` VARCHAR(150) NOT NULL,
            `logo_url` VARCHAR(255) NULL,
            `tagline` VARCHAR(255) NULL,
            `description` TEXT NULL,
            `instagram` VARCHAR(100) NULL,
            `whatsapp_default` VARCHAR(30) NULL,
            `base_city` VARCHAR(100) NOT NULL DEFAULT 'Jakarta Selatan',
            `established_year` INT NOT NULL DEFAULT 2023,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Mabar Schedules / Branch Sesi Table (Handle 3-4x mabar per minggu di hari & tempat berbeda)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schedules` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL,
            `day_name` VARCHAR(30) NOT NULL,
            `branch_area` VARCHAR(100) NOT NULL DEFAULT 'Jakarta Selatan',
            `schedule_date` DATE NOT NULL,
            `time_start` TIME NOT NULL,
            `time_end` TIME NOT NULL,
            `venue_name` VARCHAR(150) NOT NULL,
            `venue_maps_url` VARCHAR(255) NULL,
            `court_count` INT NOT NULL DEFAULT 2,
            `fee_per_person` INT NOT NULL DEFAULT 35000,
            `max_slots` INT NOT NULL DEFAULT 16,
            `pic_name` VARCHAR(100) NOT NULL DEFAULT 'Admin Powershoot',
            `pic_whatsapp` VARCHAR(30) NOT NULL DEFAULT '081299887766',
            `status` ENUM('open', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_schedules_date` (`schedule_date`),
            INDEX `idx_schedules_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Players Table (Akumulasi Total Poin Masuk, Wins, Losses, Streaks)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `players` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(30) NULL,
            `level` ENUM('Beginner', 'Intermediate', 'Advance') NOT NULL DEFAULT 'Intermediate',
            `total_matches` INT NOT NULL DEFAULT 0,
            `wins` INT NOT NULL DEFAULT 0,
            `losses` INT NOT NULL DEFAULT 0,
            `total_points` INT NOT NULL DEFAULT 0,
            `points_conceded` INT NOT NULL DEFAULT 0,
            `streak` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_players_points` (`total_points`),
            INDEX `idx_players_wins` (`wins`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Session Participants Table (Flag Pembayaran Lunas/Belum & Absensi)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `session_participants` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NOT NULL,
            `player_id` INT NOT NULL,
            `payment_status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
            `payment_amount` INT NOT NULL DEFAULT 35000,
            `paid_at` DATETIME NULL,
            `is_present` TINYINT(1) NOT NULL DEFAULT 1,
            `bench_count` INT NOT NULL DEFAULT 0,
            `notes` VARCHAR(150) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`session_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `uniq_session_player` (`session_id`, `player_id`),
            INDEX `idx_participants_payment` (`session_id`, `payment_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Matches Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `matches` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NOT NULL,
            `round_number` INT NOT NULL DEFAULT 1,
            `court_number` INT NOT NULL DEFAULT 1,
            `team_a_p1` INT NOT NULL,
            `team_a_p2` INT NOT NULL,
            `team_b_p1` INT NOT NULL,
            `team_b_p2` INT NOT NULL,
            `score_a` INT NOT NULL DEFAULT 0,
            `score_b` INT NOT NULL DEFAULT 0,
            `winner_team` ENUM('team_a', 'team_b', 'draw', 'none') NOT NULL DEFAULT 'none',
            `status` ENUM('pending', 'playing', 'completed') NOT NULL DEFAULT 'pending',
            `completed_at` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`session_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_a_p1`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_a_p2`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_b_p1`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_b_p2`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            INDEX `idx_matches_session_round` (`session_id`, `round_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 6. Gallery Documentation Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `gallery` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL,
            `image_url` VARCHAR(255) NOT NULL,
            `caption` VARCHAR(255) NULL,
            `display_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 7. Articles / Announcements Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `articles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(200) NOT NULL,
            `slug` VARCHAR(220) NOT NULL UNIQUE,
            `category` VARCHAR(50) NOT NULL DEFAULT 'Pengumuman',
            `excerpt` TEXT NOT NULL,
            `content` MEDIUMTEXT NOT NULL,
            `cover_image` VARCHAR(255) NULL,
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `views_count` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 8. Admins Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // SEED INITIAL DATA

    // A. Settings
    $chkSet = $pdo->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
    if ($chkSet == 0) {
        $stmtSet = $pdo->prepare("
            INSERT INTO `settings` (`community_name`, `tagline`, `description`, `instagram`, `whatsapp_default`, `base_city`, `established_year`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtSet->execute([
            'Powershoot Badminton Club',
            'Komunitas Badminton Kompetitif & Mabar Terorganisir Tanpa Catat Manual',
            'Powershoot Badminton adalah komunitas bulutangkis aktif di Jakarta Selatan yang mengadakan sesi main bareng 3-4 kali seminggu di berbagai GOR terbaik. Dilengkapi sistem rotasi lapangan otomatis, akumulasi poin leaderboard, dan manajemen iuran transparan.',
            '@powershoot.badminton',
            '081299887766',
            'Jakarta Selatan',
            2023
        ]);
    }

    // B. Admin Account
    $chkAdm = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ($chkAdm == 0) {
        $stmtAdm = $pdo->prepare("INSERT INTO `admins` (`username`, `password_hash`, `name`, `role`) VALUES (?, ?, ?, ?)");
        $stmtAdm->execute([
            'admin',
            password_hash('smashpoint123', PASSWORD_BCRYPT),
            'Captain Powershoot',
            'admin'
        ]);
    }

    // C. Realistic Players with Total Accumulated Points
    $chkPly = $pdo->query("SELECT COUNT(*) FROM `players`")->fetchColumn();
    if ($chkPly == 0) {
        $playersData = [];
    }

    // D. Multi-Branch Mabar Schedules (3-4 kali seminggu di hari & GOR berbeda)
    $chkSch = $pdo->query("SELECT COUNT(*) FROM `schedules`")->fetchColumn();
    if ($chkSch == 0) {
        $today = new DateTime();
        
        $wed = clone $today;
        if ($wed->format('N') != 3) $wed->modify('next wednesday');
        
        $fri = clone $today;
        if ($fri->format('N') != 5) $fri->modify('next friday');

        $sat = clone $today;
        if ($sat->format('N') != 6) $sat->modify('next saturday');

        $sun = clone $today;
        if ($sun->format('N') != 7) $sun->modify('next sunday');

        $schedulesData = [
            [
                'Branch 1: Mabar Rutin Rabu Malam (Pancoran)',
                'Rabu Malam',
                'Pancoran, Jakarta Selatan',
                $wed->format('Y-m-d'),
                '19:00:00',
                '22:00:00',
                'GOR Bintang Badminton Arena (Court 1 & 2)',
                'https://maps.google.com/?q=GOR+Badminton',
                2,
                35000,
                16,
                'Kapten Dimas (PIC Pancoran)',
                '081299887701',
                'open',
                'Shuttlecock Bulu Angsa Berkualitas disediakan. Pembagian lapangan otomatis & skor masuk leaderboard.'
            ],
            [
                'Branch 2: Mabar Intensif Jumat Malam (Fatmawati)',
                'Jumat Malam',
                'Fatmawati, Jakarta Selatan',
                $fri->format('Y-m-d'),
                '20:00:00',
                '23:00:00',
                'GOR Master Sport Arena (Court 3, 4 & 5)',
                'https://maps.google.com/?q=GOR+Master+Sport',
                3,
                40000,
                24,
                'Coach Ryan (PIC Fatmawati)',
                '081299887702',
                'open',
                '3 Lapangan karpet tebal. Format rotasi bertingkat Mexicano Ladder.'
            ],
            [
                'Branch 3: Weekend Morning Smash (Tebet)',
                'Sabtu Pagi',
                'Tebet, Jakarta Selatan',
                $sat->format('Y-m-d'),
                '07:00:00',
                '10:00:00',
                'GOR Cempaka Court (Court A & B)',
                'https://maps.google.com/?q=GOR+Cempaka',
                2,
                35000,
                16,
                'Bagas (PIC Tebet)',
                '081299887703',
                'open',
                'Sesi pagi bugar. Minuman isotonik gratis untuk peserta yang hadir on-time.'
            ],
            [
                'Branch 4: Sunday Sunset Social (Kemang)',
                'Minggu Sore',
                'Kemang, Jakarta Selatan',
                $sun->format('Y-m-d'),
                '16:00:00',
                '19:00:00',
                'Kemang Sport Hub (Court 1 & 2)',
                'https://maps.google.com/?q=Kemang+Sport+Hub',
                2,
                45000,
                16,
                'Fajar (PIC Kemang)',
                '081299887704',
                'open',
                'Sesi santai & fun doubles. Ramah untuk pemula dan intermediate.'
            ]
        ];

        $stmtS = $pdo->prepare("
            INSERT INTO `schedules` 
            (`title`, `day_name`, `branch_area`, `schedule_date`, `time_start`, `time_end`, `venue_name`, `venue_maps_url`, `court_count`, `fee_per_person`, `max_slots`, `pic_name`, `pic_whatsapp`, `status`, `notes`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($schedulesData as $s) {
            $stmtS->execute($s);
        }

        // Attach 12 participants to First Session with realistic Payment Flags (Lunas vs Belum Lunas)
        $firstSessionId = (int)$pdo->query("SELECT id FROM `schedules` ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($firstSessionId > 0) {
            $playerIds = $pdo->query("SELECT id FROM `players` LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);
            $stmtSP = $pdo->prepare("
                INSERT INTO `session_participants` (`session_id`, `player_id`, `payment_status`, `payment_amount`, `paid_at`, `is_present`, `bench_count`)
                VALUES (?, ?, ?, ?, ?, 1, 0)
                ON DUPLICATE KEY UPDATE `is_present` = 1
            ");
            foreach ($playerIds as $idx => $pId) {
                $isPaid = ($idx < 8); // 8 paid, 4 unpaid
                $paidAt = $isPaid ? date('Y-m-d H:i:s') : null;
                $stmtSP->execute([$firstSessionId, $pId, ($isPaid ? 'paid' : 'unpaid'), 35000, $paidAt]);
            }
        }
    }

    // E. Gallery Documentation
    $chkGal = $pdo->query("SELECT COUNT(*) FROM `gallery`")->fetchColumn();
    if ($chkGal == 0) {
        $galleryData = [
            ['Dokumentasi Mabar Rabu Malam Pancoran', 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80', 'Mabar seru 16 pemain di GOR Bintang Arena', 1],
            ['Sesi Pagi Bugar Tebet', 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80', 'Pemanasan dan rotasi ganda di GOR Cempaka', 2],
            ['Internal Fun Tournament Musim 1', 'https://images.unsplash.com/photo-1599586120429-48281b6f0ece?auto=format&fit=crop&w=1200&q=80', 'Penyerahan medali dan raket untuk MVP', 3],
            ['Foto Bersama Komunitas Powershoot', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1200&q=80', 'Kompak dan menjunjung tinggi sportivitas di lapangan', 4],
        ];
        $stmtG = $pdo->prepare("INSERT INTO `gallery` (`title`, `image_url`, `caption`, `display_order`) VALUES (?, ?, ?, ?)");
        foreach ($galleryData as $g) {
            $stmtG->execute($g);
        }
    }

    // F. Articles
    $chkArt = $pdo->query("SELECT COUNT(*) FROM `articles`")->fetchColumn();
    if ($chkArt == 0) {
        $articlesData = [
            [
                'Tata Tertib Mabar & Standar Shuttlecock Powershoot 2026',
                'tata-tertib-mabar-standar-shuttlecock-2026',
                'Aturan Lapangan',
                'Wajib sepatu sol karet non-marking, shuttlecock Standar Turnamen bulu angsa, dan konfirmasi kehadiran H-4 jam.',
                "Demi kenyamanan bersama seluruh anggota komunitas dalam setiap sesi mabar mingguan:\n\n1. **Sepatu Lapangan:** Wajib menggunakan sepatu khusus badminton dengan outsole karet non-marking demi menjaga karpet lapangan dan mencegah cedera lutut.\n2. **Shuttlecock Resmi:** Menggunakan shuttlecock bulu angsa resmi Standar Turnamen yang telah teruji kestabilan putarannya.\n3. **Pembayaran Iuran:** Harap melunasi iuran kok via QRIS sebelum sesi dimulai atau tunai kepada PIC di lokasi GOR.",
                'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80'
            ],
            [
                'Sistem Akumulasi Poin Leaderboard: Mengapa Setiap Poin Lapangan Berharga',
                'sistem-akumulasi-poin-leaderboard',
                'Sistem Poin',
                'Di Powershoot, peringkat tidak hanya dihitung dari menang/kalah, melainkan akumulasi poin nyata yang dicetak di setiap game!',
                "Dalam sistem Powershoot Badminton Management, setiap poin yang kamu cetak dalam pertandingan langsung diakumulasikan ke profilmu.\n\nMeskipun kalah dengan skor tipis 19-21, kamu tetap mendapatkan 19 poin untuk mendongkrak posisimu di Leaderboard Hall of Fame Komunitas. Jadi, jangan pernah menyerah di setiap reli shuttlecock!",
                'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80'
            ]
        ];
        $stmtA = $pdo->prepare("INSERT INTO `articles` (`title`, `slug`, `category`, `excerpt`, `content`, `cover_image`, `is_published`) VALUES (?, ?, ?, ?, ?, ?, 1)");
        foreach ($articlesData as $a) {
            $stmtA->execute($a);
        }
    }

    // Incremental column migrations for existing databases
    try {
        $pdo->exec("
            ALTER TABLE `schedules` 
              ADD COLUMN IF NOT EXISTS `pic_id` INT NULL AFTER `pic_whatsapp`,
              ADD COLUMN IF NOT EXISTS `completed_at` DATETIME NULL AFTER `status`;
        ");
        $pdo->exec("
            ALTER TABLE `admins` 
              ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL AFTER `name`,
              ADD COLUMN IF NOT EXISTS `assigned_session_id` INT NULL AFTER `role`;
        ");
        $pdo->exec("
            ALTER TABLE `settings` 
              ADD COLUMN IF NOT EXISTS `hero_animation_enabled` TINYINT(1) NOT NULL DEFAULT 1;
        ");
    } catch (Exception $ign) {
        // Ignored if columns already present or db engine doesn't support IF NOT EXISTS in ALTER
    }

    return true;
} catch (Exception $e) {
    throw new Exception("Setup Failed: " . $e->getMessage());
}
