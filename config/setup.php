<?php
// config/setup.php
// Powershoot Badminton Management System — Installer & Schema Migrator
//
// Mode pemakaian:
//   php config/setup.php            -> instalasi bersih (setting + 1 akun admin)
//   php config/setup.php --demo     -> instalasi + data demo siap presentasi
//
// File ini juga dipanggil otomatis oleh core/Database.php ketika database
// belum ada. Setelah instalasi produksi selesai, hapus atau kunci file ini.

$config = require __DIR__ . '/database.php';

if (!defined('SMASHPOINT_INITIAL_ADMIN_USER')) {
    define('SMASHPOINT_INITIAL_ADMIN_USER', 'admin');
}
if (!defined('SMASHPOINT_INITIAL_ADMIN_PASSWORD')) {
    // WAJIB diganti setelah login pertama melalui menu Keamanan Akun.
    define('SMASHPOINT_INITIAL_ADMIN_PASSWORD', 'admin123');
}

if (!function_exists('smashpointColumnExists')) {
    function smashpointColumnExists(PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}

if (!function_exists('smashpointEnsureColumn')) {
    function smashpointEnsureColumn(PDO $pdo, string $table, string $column, string $definition): void {
        if (!smashpointColumnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}

try {
    $dsnNoDb = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $pdoInit = new PDO($dsnNoDb, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $dbName = $config['database'];
    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$dbName};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

    // ---------------------------------------------------------------------
    // 1. SETTINGS
    // ---------------------------------------------------------------------
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `community_name` VARCHAR(150) NOT NULL,
            `logo_url` VARCHAR(255) NULL,
            `hero_image_url` VARCHAR(255) NULL,
            `hero_animation_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `tagline` VARCHAR(255) NULL,
            `description` TEXT NULL,
            `instagram` VARCHAR(100) NULL,
            `whatsapp_default` VARCHAR(30) NULL,
            `base_city` VARCHAR(100) NOT NULL DEFAULT 'Jakarta Selatan',
            `meta_title` VARCHAR(255) NULL,
            `meta_description` TEXT NULL,
            `focus_keyword` VARCHAR(150) NULL,
            `meta_keywords` TEXT NULL,
            `og_title` VARCHAR(255) NULL,
            `og_description` TEXT NULL,
            `og_image_url` VARCHAR(255) NULL,
            `canonical_url` VARCHAR(255) NULL,
            `robots_meta` VARCHAR(50) NOT NULL DEFAULT 'index, follow',
            `established_year` INT NOT NULL DEFAULT 2023,
            `hero_title` TEXT NULL,
            `hero_subtitle` TEXT NULL,
            `hero_cta_text` VARCHAR(150) NULL DEFAULT 'Pilih Jadwal & Daftar via WhatsApp PIC →',
            `announcement_text` VARCHAR(255) NULL DEFAULT 'Komunitas Badminton Resmi Jakarta Selatan (Est. 2023)',
            `announcement_subtext` VARCHAR(150) NULL DEFAULT 'Slot Dibuka Setiap Pekan',
            `step_1_title` VARCHAR(150) NULL DEFAULT 'Pilih Jadwal & Hubungi PIC WA',
            `step_1_desc` TEXT NULL,
            `step_2_title` VARCHAR(150) NULL DEFAULT 'Konfirmasi & Selesaikan Iuran',
            `step_2_desc` TEXT NULL,
            `step_3_title` VARCHAR(150) NULL DEFAULT 'Datang & Main Sesuai Rotasi Otomatis',
            `step_3_desc` TEXT NULL,
            `faq_json` LONGTEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ---------------------------------------------------------------------
    // 2. ADMINS / PIC ACCOUNTS
    // ---------------------------------------------------------------------
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(30) NULL,
            `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
            `assigned_session_id` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ---------------------------------------------------------------------
    // 3. PLAYERS
    // ---------------------------------------------------------------------
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

    // ---------------------------------------------------------------------
    // 4. SCHEDULES / SESSIONS
    // ---------------------------------------------------------------------
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
            `venue_address` VARCHAR(255) NULL,
            `venue_maps_url` VARCHAR(255) NULL,
            `court_count` INT NOT NULL DEFAULT 2,
            `court_desc` VARCHAR(100) NULL DEFAULT 'Lapangan 1 - 3',
            `fee_per_person` INT NOT NULL DEFAULT 35000,
            `max_slots` INT NOT NULL DEFAULT 16,
            `facilities` VARCHAR(255) NULL,
            `pic_id` INT NULL,
            `pic_name` VARCHAR(100) NOT NULL DEFAULT 'Admin Powershoot',
            `pic_whatsapp` VARCHAR(30) NOT NULL DEFAULT '081299887766',
            `host_name` VARCHAR(100) NULL,
            `bank_info` VARCHAR(255) NULL,
            `cancellation_rules` VARCHAR(255) NULL,
            `status` ENUM('open', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
            `completed_at` DATETIME NULL,
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_schedules_date` (`schedule_date`),
            INDEX `idx_schedules_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ---------------------------------------------------------------------
    // 5. SESSION PARTICIPANTS
    // ---------------------------------------------------------------------
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `session_participants` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NOT NULL,
            `player_id` INT NOT NULL,
            `payment_status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
            `payment_amount` INT NOT NULL DEFAULT 35000,
            `paid_at` DATETIME NULL,
            `is_present` TINYINT(1) NOT NULL DEFAULT 1,
            `status` ENUM('confirmed', 'waiting_list') NOT NULL DEFAULT 'confirmed',
            `bench_count` INT NOT NULL DEFAULT 0,
            `notes` VARCHAR(150) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`session_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `uniq_session_player` (`session_id`, `player_id`),
            INDEX `idx_participants_payment` (`session_id`, `payment_status`),
            INDEX `idx_sess_status` (`session_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ---------------------------------------------------------------------
    // 6. MATCHES
    // ---------------------------------------------------------------------
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

    // ---------------------------------------------------------------------
    // 7. GALLERY
    // ---------------------------------------------------------------------
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `gallery` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL,
            `image_url` VARCHAR(255) NOT NULL,
            `caption` VARCHAR(255) NULL,
            `session_id` INT NULL,
            `activity_date` DATE NULL,
            `media_type` ENUM('photo', 'video') NOT NULL DEFAULT 'photo',
            `video_url` VARCHAR(255) NULL,
            `display_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ---------------------------------------------------------------------
    // 8. ARTICLES
    // ---------------------------------------------------------------------
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

    // ---------------------------------------------------------------------
    // 9. FINANCIAL RECORDS
    // ---------------------------------------------------------------------
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `financial_records` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NULL,
            `type` ENUM('income', 'expense') NOT NULL DEFAULT 'expense',
            `category` VARCHAR(100) NOT NULL DEFAULT 'Lain-lain',
            `amount` INT NOT NULL DEFAULT 0,
            `title` VARCHAR(200) NOT NULL,
            `notes` TEXT NULL,
            `record_date` DATE NOT NULL,
            `created_by` INT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_fin_session` (`session_id`),
            INDEX `idx_fin_date` (`record_date`),
            INDEX `idx_fin_type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ---------------------------------------------------------------------
    // 10. PORTABLE MIGRATIONS FOR EXISTING DATABASES
    //     (CREATE TABLE IF NOT EXISTS tidak menambah kolom ke tabel lama)
    // ---------------------------------------------------------------------
    smashpointEnsureColumn($pdo, 'settings', 'hero_image_url', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'hero_animation_enabled', "TINYINT(1) NOT NULL DEFAULT 1");
    smashpointEnsureColumn($pdo, 'settings', 'meta_title', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'meta_description', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'focus_keyword', "VARCHAR(150) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'meta_keywords', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'og_title', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'og_description', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'og_image_url', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'canonical_url', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'robots_meta', "VARCHAR(50) NOT NULL DEFAULT 'index, follow'");
    smashpointEnsureColumn($pdo, 'settings', 'hero_title', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'hero_subtitle', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'hero_cta_text', "VARCHAR(150) NULL DEFAULT 'Pilih Jadwal & Daftar via WhatsApp PIC →'");
    smashpointEnsureColumn($pdo, 'settings', 'announcement_text', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'announcement_subtext', "VARCHAR(150) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'step_1_title', "VARCHAR(150) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'step_1_desc', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'step_2_title', "VARCHAR(150) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'step_2_desc', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'step_3_title', "VARCHAR(150) NULL");
    smashpointEnsureColumn($pdo, 'settings', 'step_3_desc', "TEXT NULL");
    smashpointEnsureColumn($pdo, 'settings', 'faq_json', "LONGTEXT NULL");

    smashpointEnsureColumn($pdo, 'admins', 'phone', "VARCHAR(30) NULL");
    smashpointEnsureColumn($pdo, 'admins', 'assigned_session_id', "INT NULL");

    smashpointEnsureColumn($pdo, 'schedules', 'completed_at', "DATETIME NULL");
    smashpointEnsureColumn($pdo, 'schedules', 'facilities', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'schedules', 'venue_address', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'schedules', 'bank_info', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'schedules', 'host_name', "VARCHAR(100) NULL");
    smashpointEnsureColumn($pdo, 'schedules', 'cancellation_rules', "VARCHAR(255) NULL");
    smashpointEnsureColumn($pdo, 'schedules', 'court_desc', "VARCHAR(100) NULL");
    smashpointEnsureColumn($pdo, 'schedules', 'pic_id', "INT NULL");

    smashpointEnsureColumn($pdo, 'session_participants', 'status', "ENUM('confirmed', 'waiting_list') NOT NULL DEFAULT 'confirmed'");
    smashpointEnsureColumn($pdo, 'session_participants', 'payment_amount', "INT NOT NULL DEFAULT 35000");
    smashpointEnsureColumn($pdo, 'session_participants', 'paid_at', "DATETIME NULL");

    smashpointEnsureColumn($pdo, 'gallery', 'session_id', "INT NULL");
    smashpointEnsureColumn($pdo, 'gallery', 'activity_date', "DATE NULL");
    smashpointEnsureColumn($pdo, 'gallery', 'media_type', "ENUM('photo', 'video') NOT NULL DEFAULT 'photo'");
    smashpointEnsureColumn($pdo, 'gallery', 'video_url', "VARCHAR(255) NULL");

    smashpointEnsureColumn($pdo, 'financial_records', 'created_by', "INT NULL");

    // ---------------------------------------------------------------------
    // SEED: SETTINGS (always required)
    // ---------------------------------------------------------------------
    $chkSet = $pdo->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
    if ((int)$chkSet === 0) {
        $stmtSet = $pdo->prepare("
            INSERT INTO `settings`
                (`community_name`, `tagline`, `description`, `instagram`, `whatsapp_default`,
                 `base_city`, `established_year`, `hero_title`, `hero_subtitle`, `hero_cta_text`,
                 `announcement_text`, `announcement_subtext`,
                 `step_1_title`, `step_1_desc`, `step_2_title`, `step_2_desc`, `step_3_title`, `step_3_desc`,
                 `robots_meta`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtSet->execute([
            'Nama Komunitas Badminton',
            'Komunitas Badminton Kompetitif & Mabar Terorganisir',
            'Ganti deskripsi ini melalui menu Pengaturan Komunitas di CMS Admin.',
            '@nama.komunitas',
            '081200000000',
            'Jakarta',
            (int)date('Y'),
            'Main Badminton Bareng Tanpa Repot Cari Lawan & Lapangan.',
            'Daftar ke PIC via WhatsApp, datang ke GOR, dan nikmati rotasi lapangan yang adil.',
            'Pilih Jadwal & Daftar via WhatsApp PIC →',
            'Komunitas Badminton Resmi',
            'Slot Dibuka Setiap Pekan',
            'Pilih Jadwal & Hubungi PIC WA',
            'Cek jadwal mabar, sisa kuota, lalu hubungi PIC sesi melalui WhatsApp.',
            'Konfirmasi & Selesaikan Iuran',
            'PIC memverifikasi pendaftaran. Selesaikan pembayaran sesuai instruksi PIC.',
            'Datang & Main Sesuai Rotasi Otomatis',
            'Bawa raket dan sepatu non-marking. Shuttlecock disediakan panitia.',
            'index, follow'
        ]);
    }

    // ---------------------------------------------------------------------
    // SEED: ADMIN ACCOUNT (always required)
    // ---------------------------------------------------------------------
    $chkAdm = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ((int)$chkAdm === 0) {
        $stmtAdm = $pdo->prepare("
            INSERT INTO `admins` (`username`, `password_hash`, `name`, `role`)
            VALUES (?, ?, ?, 'admin')
        ");
        $stmtAdm->execute([
            SMASHPOINT_INITIAL_ADMIN_USER,
            password_hash(SMASHPOINT_INITIAL_ADMIN_PASSWORD, PASSWORD_BCRYPT),
            'Administrator'
        ]);
    }

    // ---------------------------------------------------------------------
    // OPTIONAL DEMO DATA (CLI: php config/setup.php --demo)
    // ---------------------------------------------------------------------
    $argv = isset($argv) && is_array($argv) ? $argv : [];
    $seedDemo = (PHP_SAPI === 'cli') && in_array('--demo', $argv, true);

    if ($seedDemo) {
        $chkPly = $pdo->query("SELECT COUNT(*) FROM `players`")->fetchColumn();
        if ((int)$chkPly === 0) {
            $demoPlayers = [
                ['Dori', '081200000001', 'Intermediate'],
                ['Alv', '081200000002', 'Intermediate'],
                ['Wikan', '081200000003', 'Advance'],
                ['Hilmy', '081200000004', 'Intermediate'],
                ['Fadil', '081200000005', 'Beginner'],
                ['Ali', '081200000006', 'Advance'],
                ['Setiawan', '081200000007', 'Intermediate'],
                ['Tr', '081200000008', 'Beginner'],
                ['Bagas', '081200000009', 'Intermediate'],
                ['Ryan', '081200000010', 'Advance'],
                ['Fajar', '081200000011', 'Beginner'],
                ['Dimas', '081200000012', 'Intermediate'],
            ];
            $stmtPly = $pdo->prepare("INSERT INTO `players` (`name`, `phone`, `level`) VALUES (?, ?, ?)");
            foreach ($demoPlayers as $p) {
                $stmtPly->execute($p);
            }
        }

        $chkSch = $pdo->query("SELECT COUNT(*) FROM `schedules`")->fetchColumn();
        if ((int)$chkSch === 0) {
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
                    'Demo: Mabar Rutin Rabu Malam',
                    'Rabu Malam',
                    'Pancoran, Jakarta Selatan',
                    $wed->format('Y-m-d'),
                    '19:00:00',
                    '22:00:00',
                    'GOR Bintang Badminton Arena',
                    'https://maps.google.com/?q=GOR+Badminton',
                    2,
                    35000,
                    16,
                    'Kapten Dimas',
                    '081299887701',
                    'open',
                    'Shuttlecock disediakan. Rotasi otomatis dan skor masuk leaderboard.'
                ],
                [
                    'Demo: Mabar Intensif Jumat Malam',
                    'Jumat Malam',
                    'Fatmawati, Jakarta Selatan',
                    $fri->format('Y-m-d'),
                    '20:00:00',
                    '23:00:00',
                    'GOR Master Sport Arena',
                    'https://maps.google.com/?q=GOR+Master+Sport',
                    3,
                    40000,
                    24,
                    'Coach Ryan',
                    '081299887702',
                    'open',
                    'Tiga lapangan. Format rotasi bertingkat.'
                ],
                [
                    'Demo: Weekend Morning Smash',
                    'Sabtu Pagi',
                    'Tebet, Jakarta Selatan',
                    $sat->format('Y-m-d'),
                    '07:00:00',
                    '10:00:00',
                    'GOR Cempaka Court',
                    'https://maps.google.com/?q=GOR+Cempaka',
                    2,
                    35000,
                    16,
                    'Bagas',
                    '081299887703',
                    'open',
                    'Sesi pagi bugar untuk semua level.'
                ],
                [
                    'Demo: Sunday Sunset Social',
                    'Minggu Sore',
                    'Kemang, Jakarta Selatan',
                    $sun->format('Y-m-d'),
                    '16:00:00',
                    '19:00:00',
                    'Kemang Sport Hub',
                    'https://maps.google.com/?q=Kemang+Sport+Hub',
                    2,
                    45000,
                    16,
                    'Fajar',
                    '081299887704',
                    'open',
                    'Sesi santai dan fun doubles, ramah pemula.'
                ]
            ];

            $stmtS = $pdo->prepare("
                INSERT INTO `schedules`
                (`title`, `day_name`, `branch_area`, `schedule_date`, `time_start`, `time_end`,
                 `venue_name`, `venue_maps_url`, `court_count`, `fee_per_person`, `max_slots`,
                 `pic_name`, `pic_whatsapp`, `status`, `notes`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($schedulesData as $s) {
                $stmtS->execute($s);
            }

            $firstSessionId = (int)$pdo->query("SELECT id FROM `schedules` ORDER BY id ASC LIMIT 1")->fetchColumn();
            if ($firstSessionId > 0) {
                $playerIds = $pdo->query("SELECT id FROM `players` LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);
                $stmtSP = $pdo->prepare("
                    INSERT INTO `session_participants`
                        (`session_id`, `player_id`, `payment_status`, `payment_amount`, `paid_at`, `is_present`, `status`)
                    VALUES (?, ?, ?, ?, ?, 1, 'confirmed')
                    ON DUPLICATE KEY UPDATE `is_present` = 1
                ");
                foreach ($playerIds as $idx => $pId) {
                    $isPaid = ($idx < 8);
                    $paidAt = $isPaid ? date('Y-m-d H:i:s') : null;
                    $stmtSP->execute([$firstSessionId, $pId, ($isPaid ? 'paid' : 'unpaid'), 35000, $paidAt]);
                }
            }
        }

        $chkGal = $pdo->query("SELECT COUNT(*) FROM `gallery`")->fetchColumn();
        if ((int)$chkGal === 0) {
            $galleryData = [
                ['Dokumentasi Mabar Rabu Malam', 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80', 'Mabar seru di GOR Bintang Arena', 'photo', null, 1],
                ['Sesi Pagi Bugar Tebet', 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80', 'Pemanasan dan rotasi ganda di GOR Cempaka', 'photo', null, 2],
                ['Internal Fun Tournament', 'https://images.unsplash.com/photo-1599586120429-48281b6f0ece?auto=format&fit=crop&w=1200&q=80', 'Penyerahan medali dan raket untuk MVP', 'photo', null, 3],
                ['Foto Bersama Komunitas', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1200&q=80', 'Kompak dan menjunjung sportivitas', 'photo', null, 4],
            ];
            $stmtG = $pdo->prepare("
                INSERT INTO `gallery` (`title`, `image_url`, `caption`, `media_type`, `video_url`, `display_order`)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($galleryData as $g) {
                $stmtG->execute($g);
            }
        }

        $chkArt = $pdo->query("SELECT COUNT(*) FROM `articles`")->fetchColumn();
        if ((int)$chkArt === 0) {
            $articlesData = [
                [
                    'Tata Tertib Mabar & Standar Shuttlecock',
                    'tata-tertib-mabar-standar-shuttlecock',
                    'Aturan Lapangan',
                    'Wajib sepatu sol karet non-marking, shuttlecock standar, dan konfirmasi kehadiran sebelum sesi.',
                    "Demi kenyamanan bersama seluruh anggota komunitas:\n\n1. **Sepatu Lapangan:** Wajib sepatu badminton dengan outsole karet non-marking.\n2. **Shuttlecock:** Menggunakan shuttlecock resmi yang disediakan panitia.\n3. **Pembayaran Iuran:** Lunasi iuran sebelum sesi dimulai atau tunai kepada PIC.",
                    'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80'
                ],
                [
                    'Sistem Akumulasi Poin Leaderboard',
                    'sistem-akumulasi-poin-leaderboard',
                    'Sistem Poin',
                    'Peringkat dihitung dari akumulasi poin reli yang dicetak, bukan hanya menang atau kalah.',
                    "Setiap poin yang dicetak dalam pertandingan diakumulasikan ke profil pemain.\n\nMeskipun kalah tipis 19-21, pemain tetap mendapat 19 poin. Pemenang mendapat poin reli ditambah bonus kemenangan +5.",
                    'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80'
                ]
            ];
            $stmtA = $pdo->prepare("
                INSERT INTO `articles`
                    (`title`, `slug`, `category`, `excerpt`, `content`, `cover_image`, `is_published`)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            foreach ($articlesData as $a) {
                $stmtA->execute($a);
            }
        }
    }

    return true;
} catch (Exception $e) {
    throw new Exception("Setup Failed: " . $e->getMessage());
}
