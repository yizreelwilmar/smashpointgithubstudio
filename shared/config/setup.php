<?php
// shared/config/setup.php
// Multi-Tenant SaaS Schema Migration & Seeder for SMASHPOINT

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

    // 1. Tenants / Clubs Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `clubs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(80) NOT NULL UNIQUE,
            `name` VARCHAR(150) NOT NULL,
            `subdomain` VARCHAR(80) NOT NULL UNIQUE,
            `sport_type` VARCHAR(50) NOT NULL DEFAULT 'Badminton',
            `city` VARCHAR(100) NOT NULL DEFAULT 'Jakarta',
            `tagline` VARCHAR(255) NULL,
            `primary_color` VARCHAR(20) NOT NULL DEFAULT '#10e599',
            `whatsapp_contact` VARCHAR(30) NULL,
            `plan` ENUM('starter', 'pro', 'enterprise') NOT NULL DEFAULT 'pro',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_clubs_slug` (`slug`),
            INDEX `idx_clubs_subdomain` (`subdomain`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Users / Admins Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `club_id` INT NULL,
            `name` VARCHAR(100) NOT NULL,
            `username` VARCHAR(60) NOT NULL UNIQUE,
            `email` VARCHAR(120) NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `role` ENUM('superadmin', 'club_admin', 'organizer') NOT NULL DEFAULT 'club_admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Players Table (Multi-tenant)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `players` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `club_id` INT NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(30) NULL,
            `level` ENUM('Beginner', 'Intermediate', 'Advance') NOT NULL DEFAULT 'Intermediate',
            `total_matches` INT NOT NULL DEFAULT 0,
            `wins` INT NOT NULL DEFAULT 0,
            `losses` INT NOT NULL DEFAULT 0,
            `total_points_won` INT NOT NULL DEFAULT 0,
            `total_points_lost` INT NOT NULL DEFAULT 0,
            `streak` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
            INDEX `idx_players_club_wins` (`club_id`, `wins`),
            INDEX `idx_players_club_level` (`club_id`, `level`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Sessions Table (Mabar)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sessions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `club_id` INT NOT NULL,
            `title` VARCHAR(150) NOT NULL,
            `day_of_week` VARCHAR(20) NOT NULL,
            `session_date` DATE NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `venue_name` VARCHAR(150) NOT NULL,
            `venue_maps_url` VARCHAR(255) NULL,
            `court_count` INT NOT NULL DEFAULT 2,
            `fee_per_person` INT NOT NULL DEFAULT 35000,
            `max_slots` INT NOT NULL DEFAULT 16,
            `status` ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`club_id`) REFERENCES `clubs`(`id`) ON DELETE CASCADE,
            INDEX `idx_sessions_club_date` (`club_id`, `session_date`),
            INDEX `idx_sessions_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Session Participants Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `session_participants` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `session_id` INT NOT NULL,
            `player_id` INT NOT NULL,
            `payment_status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
            `payment_reference` VARCHAR(100) NULL,
            `is_present` TINYINT(1) NOT NULL DEFAULT 1,
            `bench_count` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `uniq_sess_player` (`session_id`, `player_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 6. Matches Table
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
            `started_at` DATETIME NULL,
            `completed_at` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_a_p1`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_a_p2`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_b_p1`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`team_b_p2`) REFERENCES `players`(`id`) ON DELETE CASCADE,
            INDEX `idx_matches_sess_round` (`session_id`, `round_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 7. Articles Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `articles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `club_id` INT NULL,
            `title` VARCHAR(200) NOT NULL,
            `slug` VARCHAR(220) NOT NULL UNIQUE,
            `category` VARCHAR(50) NOT NULL DEFAULT 'Pengumuman',
            `excerpt` TEXT NOT NULL,
            `content` MEDIUMTEXT NOT NULL,
            `cover_image` VARCHAR(255) NULL,
            `is_published` TINYINT(1) NOT NULL DEFAULT 1,
            `views_count` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_articles_club` (`club_id`, `is_published`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 8. Invoices Table (Payment Gateway Ready)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `invoices` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `club_id` INT NOT NULL,
            `session_id` INT NULL,
            `player_id` INT NULL,
            `invoice_number` VARCHAR(60) NOT NULL UNIQUE,
            `amount` INT NOT NULL,
            `payment_method` VARCHAR(50) NOT NULL DEFAULT 'manual_transfer',
            `payment_status` ENUM('pending', 'paid', 'expired', 'failed') NOT NULL DEFAULT 'pending',
            `payment_url` VARCHAR(255) NULL,
            `paid_at` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_invoices_club` (`club_id`, `payment_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 9. Seed Default Tenant: Powershoot Badminton
    $chkClub = $pdo->query("SELECT COUNT(*) FROM `clubs` WHERE `slug` = 'powershoot'")->fetchColumn();
    if ($chkClub == 0) {
        $stmtClub = $pdo->prepare("
            INSERT INTO `clubs` (`slug`, `name`, `subdomain`, `sport_type`, `city`, `tagline`, `primary_color`, `whatsapp_contact`, `plan`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtClub->execute([
            'powershoot',
            'Powershoot Badminton',
            'powershoot',
            'Badminton',
            'Jakarta Selatan',
            'Komunitas Mabar Badminton & Arena Rotasi Cerdas',
            '#10e599',
            '081299887766',
            'pro'
        ]);
        $powershootId = (int)$pdo->lastInsertId();
    } else {
        $powershootId = (int)$pdo->query("SELECT id FROM `clubs` WHERE `slug` = 'powershoot'")->fetchColumn();
    }

    // 10. Seed Admin Users
    $chkUser = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `username` = 'admin'")->fetchColumn();
    if ($chkUser == 0) {
        $stmtUser = $pdo->prepare("
            INSERT INTO `users` (`club_id`, `name`, `username`, `email`, `password_hash`, `role`)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtUser->execute([
            $powershootId,
            'Captain Powershoot',
            'admin',
            'captain@powershoot.id',
            password_hash('smashpoint123', PASSWORD_BCRYPT),
            'club_admin'
        ]);
    }

    // 11. Seed Powershoot Players
    $chkP = $pdo->query("SELECT COUNT(*) FROM `players` WHERE `club_id` = {$powershootId}")->fetchColumn();
    if ($chkP == 0) {
        $playersData = [];
    }

    // 12. Seed Sessions for Powershoot
    $chkS = $pdo->query("SELECT COUNT(*) FROM `sessions` WHERE `club_id` = {$powershootId}")->fetchColumn();
    if ($chkS == 0) {
        $today = new DateTime();
        $nextWed = clone $today;
        if ($nextWed->format('N') != 3) $nextWed->modify('next wednesday');
        $nextSat = clone $today;
        if ($nextSat->format('N') != 6) $nextSat->modify('next saturday');

        $sessionsData = [
            [
                $powershootId,
                'Powershoot Night Smash — 2 Lapangan Karpet',
                'Rabu',
                $nextWed->format('Y-m-d'),
                '19:00:00',
                '22:00:00',
                'GOR Bintang Badminton Arena (Court 1 & 2)',
                'https://maps.google.com/?q=GOR+Badminton',
                2,
                35000,
                16,
                'scheduled',
                'Kok Bulu Angsa Berkualitas disediakan. Wajib sepatu non-marking sol karet.'
            ],
            [
                $powershootId,
                'Powershoot Morning Energy Mabar',
                'Sabtu',
                $nextSat->format('Y-m-d'),
                '07:00:00',
                '10:00:00',
                'GOR Cempaka Court (Court A & B)',
                'https://maps.google.com/?q=GOR+Cempaka',
                2,
                40000,
                16,
                'scheduled',
                'Sesi pagi ceria & sarapan bersama setelah mabar.'
            ],
        ];

        $stmtS = $pdo->prepare("INSERT INTO `sessions` (`club_id`, `title`, `day_of_week`, `session_date`, `start_time`, `end_time`, `venue_name`, `venue_maps_url`, `court_count`, `fee_per_person`, `max_slots`, `status`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sessionsData as $s) {
            $stmtS->execute($s);
        }

        // Add 12 participants to Session 1
        $firstSessionId = (int)$pdo->query("SELECT id FROM `sessions` WHERE `club_id` = {$powershootId} ORDER BY id ASC LIMIT 1")->fetchColumn();
        $playerIds = $pdo->query("SELECT id FROM `players` WHERE `club_id` = {$powershootId} LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);

        $stmtSP = $pdo->prepare("INSERT INTO `session_participants` (`session_id`, `player_id`, `payment_status`, `is_present`, `bench_count`) VALUES (?, ?, ?, ?, ?)");
        foreach ($playerIds as $idx => $pId) {
            $stmtSP->execute([$firstSessionId, $pId, ($idx < 8 ? 'paid' : 'unpaid'), 1, 0]);
        }
    }

    // 13. Seed Articles for Powershoot & SaaS
    $chkArt = $pdo->query("SELECT COUNT(*) FROM `articles`")->fetchColumn();
    if ($chkArt == 0) {
        $articlesData = [
            [
                $powershootId,
                'Panduan Rotasi Lapangan: Mengapa Sistem Fair Social Lebih Adil dari Kocokan Manual',
                'panduan-rotasi-lapangan-fair-social',
                'Sistem & Aturan',
                'Kenali bagaimana algoritma rotasi lapangan Powershoot mendistribusikan waktu main dan rekan ganda secara merata.',
                "Dalam mabar komunitas Powershoot Badminton, masalah pemain yang terlalu sering duduk di bangku cadangan diselesaikan melalui sistem rotasi otomatis.\n\nSetiap ronde menghadirkan variasi partner baru dan membagi waktu istirahat secara rotatif. Poin kemenangan dan selisih angka langsung otomatis memperbarui Leaderboard Komunitas.",
                'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80',
                1,
                184
            ],
            [
                $powershootId,
                'Tata Tertib Sesi Mabar Powershoot 2026 & Standar Kok',
                'tata-tertib-sesi-mabar-powershoot-2026',
                'Pengumuman',
                'Informasi mengenai pemakaian kok resmi bulu angsa, ketentuan sepatu non-marking, dan etika saling menghargai di lapangan.',
                "Demi kenyamanan bersama seluruh anggota Powershoot Badminton:\n\n1. Sepatu khusus lapangan badminton non-marking.\n2. Shuttlecock Standar Turnamen resmi disediakan oleh panitia.\n3. Harap konfirmasi pembatalan minimal H-4 jam jika berhalangan.",
                'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=1200&q=80',
                1,
                112
            ]
        ];

        $stmtA = $pdo->prepare("INSERT INTO `articles` (`club_id`, `title`, `slug`, `category`, `excerpt`, `content`, `cover_image`, `is_published`, `views_count`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($articlesData as $a) {
            $stmtA->execute($a);
        }
    }

    return true;
} catch (Exception $e) {
    throw new Exception("Setup Failed: " . $e->getMessage());
}
