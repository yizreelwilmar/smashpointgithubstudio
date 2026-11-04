<?php
// models/Community.php
// Community Profile, CMS Content & Gallery Manager

require_once __DIR__ . '/../core/Database.php';

class Community {
    public static function getSettings() {
        $pdo = Database::getConnection();
        $row = $pdo->query("SELECT * FROM `settings` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [
                'community_name' => 'Powershoot Badminton Club',
                'tagline' => 'Komunitas Badminton Kompetitif & Ramah',
                'description' => 'Mabar rutin 3-4x seminggu di Jakarta Selatan dengan rotasi lapangan cerdas.',
                'instagram' => '@powershoot.badminton',
                'whatsapp_default' => '081299887766',
                'base_city' => 'Jakarta Selatan',
                'established_year' => 2023,
                'logo_url' => '/smashpoint/public/uploads/powershoot_logo.jpg',
                'hero_image_url' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=80',
                'hero_title' => 'Main Badminton Bareng Tanpa Repot Cari Lawan & Lapangan.',
                'hero_subtitle' => 'Selamat datang di Powershoot Badminton Club. Kami menyelenggarakan sesi main bareng ganda 4 kali setiap pekan di berbagai GOR terbaik Jakarta Selatan. Cukup daftar ke PIC WhatsApp, datang ke arena, dan nikmati pertandingan dengan pembagian giliran main yang teratur.',
                'hero_cta_text' => 'Pilih Jadwal & Daftar via WhatsApp PIC →',
                'announcement_text' => 'Komunitas Badminton Resmi Jakarta Selatan (Est. 2023)',
                'announcement_subtext' => 'Slot Dibuka Setiap Pekan',
                'step_1_title' => 'Pilih Jadwal & Hubungi PIC WA',
                'step_1_desc' => 'Cek jadwal mabar di GOR terdekat, slot kuota tersisa, lalu klik tombol pendaftaran WhatsApp PIC untuk konfirmasi kehadiran.',
                'step_2_title' => 'Konfirmasi & Selesaikan Iuran',
                'step_2_desc' => 'PIC sesi akan memverifikasi pendaftaran Anda. Cukup selesaikan pembayaran via transfer/QRIS atau tunai di lokasi mabar.',
                'step_3_title' => 'Datang & Main Sesuai Rotasi Otomatis',
                'step_3_desc' => 'Cukup bawa raket dan hadir tepat waktu. Shuttlecock bulu angsa resmi berkualitas sudah kami sediakan di lapangan.',
                'faq_json' => null
            ];
        }

        // Default fallbacks
        $baseCity = !empty($row['base_city']) ? $row['base_city'] : 'Indonesia';
        $commName = !empty($row['community_name']) ? $row['community_name'] : 'Powershoot Badminton Club';

        if (empty($row['hero_image_url'])) {
            $row['hero_image_url'] = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=800&q=80';
        }
        if (empty($row['hero_title'])) {
            $row['hero_title'] = 'Main Badminton Bareng Tanpa Repot Cari Lawan & Lapangan.';
        }

        // Auto-adapt logo URL to domain root or subfolder (Rumahweb / cPanel hosting compatibility)
        if (!empty($row['logo_url']) && strpos($row['logo_url'], 'http') !== 0) {
            $scriptDir = dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
            $baseDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
            if (substr($baseDir, -6) === '/admin') $baseDir = substr($baseDir, 0, -6);
            if (substr($baseDir, -4) === '/app') $baseDir = substr($baseDir, 0, -4);
            if (substr($baseDir, -8) === '/landing') $baseDir = substr($baseDir, 0, -8);
            
            if (strpos($row['logo_url'], '/smashpoint/') === 0) {
                $subPath = substr($row['logo_url'], 11); // e.g. /public/uploads/...
                $row['logo_url'] = ($baseDir === '') ? $subPath : ($baseDir . $subPath);
            }
        }
        if (empty($row['hero_subtitle'])) {
            $row['hero_subtitle'] = 'Selamat datang di ' . $commName . '. Kami menyelenggarakan sesi main bareng ganda setiap pekan di berbagai GOR terbaik ' . $baseCity . '. Cukup daftar ke PIC WhatsApp, datang ke arena, dan nikmati pertandingan dengan pembagian giliran main yang teratur.';
        }
        if (empty($row['hero_cta_text'])) {
            $row['hero_cta_text'] = 'Pilih Jadwal & Daftar via WhatsApp PIC →';
        }
        if (empty($row['announcement_text'])) {
            $row['announcement_text'] = 'Komunitas Badminton Resmi ' . $baseCity . ' (Est. ' . ($row['established_year'] ?? 2023) . ')';
        }
        if (empty($row['announcement_subtext'])) {
            $row['announcement_subtext'] = 'Slot Dibuka Setiap Pekan';
        }
        if (empty($row['step_1_title'])) {
            $row['step_1_title'] = 'Pilih Jadwal & Hubungi PIC WA';
        }
        if (empty($row['step_1_desc'])) {
            $row['step_1_desc'] = 'Cek jadwal mabar di GOR terdekat, slot kuota tersisa, lalu klik tombol pendaftaran WhatsApp PIC untuk konfirmasi kehadiran.';
        }
        if (empty($row['step_2_title'])) {
            $row['step_2_title'] = 'Konfirmasi & Selesaikan Iuran';
        }
        if (empty($row['step_2_desc'])) {
            $row['step_2_desc'] = 'PIC sesi akan memverifikasi pendaftaran Anda. Cukup selesaikan pembayaran via transfer/QRIS atau tunai di lokasi mabar.';
        }
        if (empty($row['step_3_title'])) {
            $row['step_3_title'] = 'Datang & Main Sesuai Rotasi Otomatis';
        }
        if (empty($row['step_3_desc'])) {
            $row['step_3_desc'] = 'Cukup bawa raket dan hadir tepat waktu. Shuttlecock bulu angsa resmi berkualitas sudah kami sediakan di lapangan.';
        }

        // SEO Fallbacks
        if (empty($row['meta_title'])) {
            $row['meta_title'] = $commName . ' — Mabar Badminton & Rotasi Lapangan ' . $baseCity;
        }
        if (empty($row['meta_description'])) {
            $row['meta_description'] = !empty($row['description']) 
                ? strip_tags($row['description']) 
                : ('Komunitas mabar badminton resmi di ' . $baseCity . ' dengan sistem rotasi ganda fair level, jadwal rutin setiap pekan, dan kok bulu angsa berkualitas.');
        }
        if (empty($row['focus_keyword'])) {
            $row['focus_keyword'] = 'mabar badminton ' . strtolower($baseCity);
        }
        if (empty($row['meta_keywords'])) {
            $row['meta_keywords'] = 'mabar badminton ' . strtolower($baseCity) . ', komunitas badminton ' . strtolower($baseCity) . ', jadwal mabar badminton, sewa lapangan badminton, sparring bulutangkis';
        }
        if (empty($row['og_title'])) {
            $row['og_title'] = $row['meta_title'];
        }
        if (empty($row['og_description'])) {
            $row['og_description'] = $row['meta_description'];
        }
        if (empty($row['og_image_url'])) {
            $row['og_image_url'] = !empty($row['hero_image_url']) ? $row['hero_image_url'] : 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1200&q=80';
        }
        if (empty($row['robots_meta'])) {
            $row['robots_meta'] = 'index, follow';
        }

        return $row;
    }

    public static function parseSeoVariables(string $text, array $settings): string {
        $commName = !empty($settings['community_name']) ? $settings['community_name'] : 'Komunitas Badminton';
        $city = !empty($settings['base_city']) ? $settings['base_city'] : 'Indonesia';
        $tagline = !empty($settings['tagline']) ? $settings['tagline'] : '';
        $year = !empty($settings['established_year']) ? (string)$settings['established_year'] : date('Y');

        $replacements = [
            '%%sitename%%' => $commName,
            '%%title%%' => $commName,
            '%%city%%' => $city,
            '%%tagline%%' => $tagline,
            '%%sep%%' => '—',
            '%%year%%' => $year,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    public static function updateSettings(array $data) {
        $pdo = Database::getConnection();
        
        $fields = [
            'community_name', 'tagline', 'description', 'instagram', 'whatsapp_default',
            'base_city', 'established_year', 'logo_url', 'hero_image_url',
            'hero_title', 'hero_subtitle', 'hero_cta_text',
            'announcement_text', 'announcement_subtext',
            'step_1_title', 'step_1_desc',
            'step_2_title', 'step_2_desc',
            'step_3_title', 'step_3_desc',
            'faq_json',
            'meta_title', 'meta_description', 'focus_keyword', 'meta_keywords',
            'og_title', 'og_description', 'og_image_url', 'canonical_url', 'robots_meta'
        ];

        $setParts = [];
        $params = [];

        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $setParts[] = "`$f` = ?";
                $params[] = $data[$f];
            }
        }

        if (empty($setParts)) {
            return false;
        }

        $sql = "UPDATE `settings` SET " . implode(", ", $setParts) . " WHERE `id` = 1";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function getGallery(int $limit = 30, ?int $sessionId = null) {
        $pdo = Database::getConnection();
        $sql = "
            SELECT g.*, s.venue_name, s.day_name, s.court_count, s.time_start 
            FROM `gallery` g 
            LEFT JOIN `schedules` s ON g.session_id = s.id 
        ";
        $params = [];

        if ($sessionId !== null) {
            $sql .= " WHERE g.session_id = ? ";
            $params[] = $sessionId;
        }

        $sql .= " ORDER BY g.created_at DESC LIMIT " . (int)$limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addGalleryPhoto(string $title, string $imageUrl, ?string $caption = null, $sessionId = null, ?string $activityDate = null) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `gallery` (`title`, `image_url`, `caption`, `session_id`, `activity_date`, `created_at`) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $cleanSessionId = !empty($sessionId) ? (int)$sessionId : null;
        $cleanDate = !empty($activityDate) ? date('Y-m-d', strtotime($activityDate)) : date('Y-m-d');
        $timestamp = date('Y-m-d H:i:s');
        return $stmt->execute([$title, $imageUrl, $caption ?: '', $cleanSessionId, $cleanDate, $timestamp]);
    }

    public static function deleteGalleryPhoto(int $id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `gallery` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    public static function formatIndoDate(string $rawDate) {
        if (empty($rawDate)) return '';
        $timestamp = strtotime($rawDate);
        if (!$timestamp) return $rawDate;

        $hariArr = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];

        $bulanArr = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $dayName = $hariArr[date('l', $timestamp)] ?? date('l', $timestamp);
        $dayNum = date('j', $timestamp);
        $monthName = $bulanArr[(int)date('n', $timestamp)] ?? date('F', $timestamp);
        $yearNum = date('Y', $timestamp);

        return $dayName . ', ' . $dayNum . ' ' . $monthName . ' ' . $yearNum;
    }
}

if (!function_exists('formatIndoDate')) {
    function formatIndoDate(string $rawDate) {
        return Community::formatIndoDate($rawDate);
    }
}
