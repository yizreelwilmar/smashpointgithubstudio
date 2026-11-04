<?php
// models/Schedule.php
// Multi-Branch Mabar Schedule Manager (Handles 3-4x weekly mabar across venues)

require_once __DIR__ . '/../core/Database.php';

class Schedule {
    public static function all() {
        $pdo = Database::getConnection();
        return $pdo->query("
            SELECT s.*, 
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.pic_name, ''), 'PIC Komunitas') AS pic_name,
                   COALESCE(NULLIF(pic.phone, ''), NULLIF(s.pic_whatsapp, ''), '') AS pic_whatsapp,
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.host_name, ''), 'PIC Lapangan') AS host_name,
                   COUNT(sp.id) as participant_count,
                   SUM(CASE WHEN sp.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                   SUM(CASE WHEN sp.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count
            FROM `schedules` s
            LEFT JOIN (
                SELECT assigned_session_id, 
                       SUBSTRING_INDEX(GROUP_CONCAT(name ORDER BY id DESC), ',', 1) AS name,
                       SUBSTRING_INDEX(GROUP_CONCAT(phone ORDER BY id DESC), ',', 1) AS phone
                FROM admins 
                WHERE role = 'pic' AND assigned_session_id IS NOT NULL 
                GROUP BY assigned_session_id
            ) pic ON pic.assigned_session_id = s.id
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            GROUP BY s.id
            ORDER BY s.schedule_date DESC, s.time_start DESC
        ")->fetchAll();
    }

    public static function upcoming(int $limit = 10) {
        $pdo = Database::getConnection();
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.pic_name, ''), 'PIC Komunitas') AS pic_name,
                   COALESCE(NULLIF(pic.phone, ''), NULLIF(s.pic_whatsapp, ''), '') AS pic_whatsapp,
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.host_name, ''), 'PIC Lapangan') AS host_name,
                   COUNT(sp.id) as participant_count,
                   SUM(CASE WHEN sp.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                   SUM(CASE WHEN sp.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count
            FROM `schedules` s
            LEFT JOIN (
                SELECT assigned_session_id, 
                       SUBSTRING_INDEX(GROUP_CONCAT(name ORDER BY id DESC), ',', 1) AS name,
                       SUBSTRING_INDEX(GROUP_CONCAT(phone ORDER BY id DESC), ',', 1) AS phone
                FROM admins 
                WHERE role = 'pic' AND assigned_session_id IS NOT NULL 
                GROUP BY assigned_session_id
            ) pic ON pic.assigned_session_id = s.id
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            WHERE s.schedule_date >= :today
            GROUP BY s.id
            ORDER BY s.schedule_date ASC, s.time_start ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.pic_name, ''), 'PIC Komunitas') AS pic_name,
                   COALESCE(NULLIF(pic.phone, ''), NULLIF(s.pic_whatsapp, ''), '') AS pic_whatsapp,
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.host_name, ''), 'PIC Lapangan') AS host_name,
                   COUNT(sp.id) as participant_count,
                   SUM(CASE WHEN sp.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                   SUM(CASE WHEN sp.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count
            FROM `schedules` s
            LEFT JOIN (
                SELECT assigned_session_id, 
                       SUBSTRING_INDEX(GROUP_CONCAT(name ORDER BY id DESC), ',', 1) AS name,
                       SUBSTRING_INDEX(GROUP_CONCAT(phone ORDER BY id DESC), ',', 1) AS phone
                FROM admins 
                WHERE role = 'pic' AND assigned_session_id IS NOT NULL 
                GROUP BY assigned_session_id
            ) pic ON pic.assigned_session_id = s.id
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            WHERE s.id = ?
            GROUP BY s.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function forPic(int $picId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.pic_name, ''), 'PIC Komunitas') AS pic_name,
                   COALESCE(NULLIF(pic.phone, ''), NULLIF(s.pic_whatsapp, ''), '') AS pic_whatsapp,
                   COALESCE(NULLIF(pic.name, ''), NULLIF(s.host_name, ''), 'PIC Lapangan') AS host_name,
                   COUNT(sp.id) as participant_count,
                   SUM(CASE WHEN sp.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                   SUM(CASE WHEN sp.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count
            FROM `schedules` s
            LEFT JOIN (
                SELECT id, assigned_session_id, name, phone
                FROM admins 
                WHERE role = 'pic'
            ) pic ON (pic.assigned_session_id = s.id OR pic.id = :picId)
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            WHERE (pic.id = :picId OR s.pic_name LIKE (SELECT CONCAT('%', name, '%') FROM admins WHERE id = :picId LIMIT 1))
            GROUP BY s.id
            ORDER BY s.schedule_date DESC, s.time_start DESC
        ");
        $stmt->execute([':picId' => $picId]);
        $res = $stmt->fetchAll();
        return !empty($res) ? $res : self::all();
    }

    public static function create(array $data) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `schedules` 
            (`title`, `day_name`, `branch_area`, `schedule_date`, `time_start`, `time_end`, `venue_name`, `venue_address`, `venue_maps_url`, `court_count`, `court_desc`, `fee_per_person`, `max_slots`, `facilities`, `pic_name`, `pic_whatsapp`, `host_name`, `bank_info`, `cancellation_rules`, `status`, `notes`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['day_name'],
            isset($data['branch_area']) ? $data['branch_area'] : 'Jakarta Selatan',
            $data['schedule_date'],
            $data['time_start'],
            $data['time_end'],
            $data['venue_name'],
            isset($data['venue_address']) ? $data['venue_address'] : null,
            isset($data['venue_maps_url']) ? $data['venue_maps_url'] : null,
            isset($data['court_count']) ? (int)$data['court_count'] : 2,
            isset($data['court_desc']) ? $data['court_desc'] : 'Lapangan 1 - 3',
            isset($data['fee_per_person']) ? (int)$data['fee_per_person'] : 35000,
            isset($data['max_slots']) ? (int)$data['max_slots'] : 16,
            isset($data['facilities']) ? $data['facilities'] : 'Karpet Vinyl BWF, Kok Bulu Angsa Berkualitas, Lampu LED Anti-Silau, Toilet & Kantin',
            isset($data['pic_name']) ? $data['pic_name'] : 'Admin Komunitas',
            isset($data['pic_whatsapp']) ? $data['pic_whatsapp'] : '081234567890',
            isset($data['host_name']) ? $data['host_name'] : 'PIC Lapangan',
            isset($data['bank_info']) ? $data['bank_info'] : 'BCA 1234567890 a.n Bendahara',
            isset($data['cancellation_rules']) ? $data['cancellation_rules'] : 'Konfirmasi pembatalan slot maksimal H-1 atau cari pemain pengganti.',
            isset($data['status']) ? $data['status'] : 'open',
            isset($data['notes']) ? $data['notes'] : null
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `schedules` 
            SET `title` = :title,
                `day_name` = :day_name,
                `branch_area` = :branch_area,
                `schedule_date` = :schedule_date,
                `time_start` = :time_start,
                `time_end` = :time_end,
                `venue_name` = :venue_name,
                `venue_address` = :venue_address,
                `venue_maps_url` = :venue_maps_url,
                `court_count` = :court_count,
                `court_desc` = :court_desc,
                `fee_per_person` = :fee_per_person,
                `max_slots` = :max_slots,
                `facilities` = :facilities,
                `pic_name` = :pic_name,
                `pic_whatsapp` = :pic_whatsapp,
                `host_name` = :host_name,
                `bank_info` = :bank_info,
                `cancellation_rules` = :cancellation_rules,
                `notes` = :notes
            WHERE `id` = :id
        ");
        return $stmt->execute([
            ':title' => $data['title'],
            ':day_name' => $data['day_name'],
            ':branch_area' => isset($data['branch_area']) ? $data['branch_area'] : 'Jakarta Selatan',
            ':schedule_date' => $data['schedule_date'],
            ':time_start' => $data['time_start'],
            ':time_end' => $data['time_end'],
            ':venue_name' => $data['venue_name'],
            ':venue_address' => isset($data['venue_address']) ? $data['venue_address'] : null,
            ':venue_maps_url' => isset($data['venue_maps_url']) ? $data['venue_maps_url'] : null,
            ':court_count' => (int)$data['court_count'],
            ':court_desc' => isset($data['court_desc']) ? $data['court_desc'] : 'Lapangan 1 - 3',
            ':fee_per_person' => (int)$data['fee_per_person'],
            ':max_slots' => (int)$data['max_slots'],
            ':facilities' => isset($data['facilities']) ? $data['facilities'] : 'Karpet Vinyl BWF, Kok Bulu Angsa Berkualitas, Lampu LED Anti-Silau, Toilet & Kantin',
            ':pic_name' => isset($data['pic_name']) ? $data['pic_name'] : 'Admin Komunitas',
            ':pic_whatsapp' => isset($data['pic_whatsapp']) ? $data['pic_whatsapp'] : '081234567890',
            ':host_name' => isset($data['host_name']) ? $data['host_name'] : 'PIC Lapangan',
            ':bank_info' => isset($data['bank_info']) ? $data['bank_info'] : 'BCA 1234567890 a.n Bendahara',
            ':cancellation_rules' => isset($data['cancellation_rules']) ? $data['cancellation_rules'] : 'Konfirmasi pembatalan slot maksimal H-1 atau cari pemain pengganti.',
            ':notes' => isset($data['notes']) ? $data['notes'] : null,
            ':id' => $id
        ]);
    }

    public static function delete(int $id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `schedules` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    public static function updateStatus(int $id, string $status) {
        $pdo = Database::getConnection();
        if ($status === 'completed') {
            try {
                $stmt = $pdo->prepare("UPDATE `schedules` SET `status` = 'completed', `completed_at` = NOW() WHERE `id` = ?");
                return $stmt->execute([$id]);
            } catch (PDOException $e) {
                $stmt = $pdo->prepare("UPDATE `schedules` SET `status` = 'completed' WHERE `id` = ?");
                return $stmt->execute([$id]);
            }
        }
        $stmt = $pdo->prepare("UPDATE `schedules` SET `status` = ? WHERE `id` = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function completeSession(int $id) {
        return self::updateStatus($id, 'completed');
    }

    public static function duplicateForNextWeek(int $id): ?int {
        $source = self::find($id);
        if (!$source) return null;

        $nextDate = date('Y-m-d', strtotime($source['schedule_date'] . ' +7 days'));
        
        $data = [
            'title' => $source['title'],
            'day_name' => $source['day_name'],
            'branch_area' => $source['branch_area'],
            'schedule_date' => $nextDate,
            'time_start' => $source['time_start'],
            'time_end' => $source['time_end'],
            'venue_name' => $source['venue_name'],
            'venue_address' => $source['venue_address'],
            'venue_maps_url' => $source['venue_maps_url'],
            'court_count' => (int)$source['court_count'],
            'court_desc' => $source['court_desc'],
            'fee_per_person' => (int)$source['fee_per_person'],
            'max_slots' => (int)$source['max_slots'],
            'facilities' => $source['facilities'],
            'pic_name' => $source['pic_name'],
            'pic_whatsapp' => $source['pic_whatsapp'],
            'host_name' => $source['host_name'],
            'bank_info' => $source['bank_info'],
            'cancellation_rules' => $source['cancellation_rules'],
            'status' => 'open',
            'notes' => 'Sesi lanjutan rutin pekan berikutnya dari ' . $source['venue_name']
        ];

        return self::create($data);
    }
}
