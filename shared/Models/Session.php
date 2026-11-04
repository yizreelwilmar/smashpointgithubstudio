<?php
// shared/Models/Session.php
// Weekly Mabar Sessions Model (Scoped by Club / Tenant)

require_once __DIR__ . '/../core/Database.php';

class Session {
    public static function all(int $clubId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COUNT(sp.id) as participant_count
            FROM `sessions` s
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            WHERE s.club_id = ?
            GROUP BY s.id
            ORDER BY s.session_date DESC, s.start_time DESC
        ");
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public static function upcoming(int $clubId, int $limit = 10) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COUNT(sp.id) as participant_count
            FROM `sessions` s
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            WHERE s.club_id = :club_id AND s.session_date >= CURDATE()
            GROUP BY s.id
            ORDER BY s.session_date ASC, s.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':club_id', $clubId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COUNT(sp.id) as participant_count
            FROM `sessions` s
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            WHERE s.id = ?
            GROUP BY s.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getParticipants(int $sessionId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, p.name, p.level, p.phone, p.total_matches, p.wins, p.losses, p.streak
            FROM `session_participants` sp
            JOIN `players` p ON sp.player_id = p.id
            WHERE sp.session_id = ?
            ORDER BY sp.is_present DESC, p.name ASC
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    public static function addParticipant(int $sessionId, int $playerId, string $paymentStatus = 'unpaid', $paymentRef = null) {
        $pdo = Database::getConnection();
        $session = self::find($sessionId);
        if (!$session) return false;

        $currentCount = (int)$session['participant_count'];
        if ($currentCount >= (int)$session['max_slots']) {
            throw new RuntimeException("Sesi mabar sudah penuh! Kuota maksimal {$session['max_slots']} pemain telah tercapai.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO `session_participants` (`session_id`, `player_id`, `payment_status`, `payment_reference`, `is_present`)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE `is_present` = 1, `payment_status` = VALUES(`payment_status`), `payment_reference` = VALUES(`payment_reference`)
        ");
        return $stmt->execute([$sessionId, $playerId, $paymentStatus, $paymentRef]);
    }

    public static function toggleAttendance(int $sessionId, int $playerId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `session_participants` 
            SET `is_present` = IF(`is_present` = 1, 0, 1)
            WHERE `session_id` = ? AND `player_id` = ?
        ");
        return $stmt->execute([$sessionId, $playerId]);
    }

    public static function create(int $clubId, array $data) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `sessions` 
            (`club_id`, `title`, `day_of_week`, `session_date`, `start_time`, `end_time`, `venue_name`, `venue_maps_url`, `court_count`, `fee_per_person`, `max_slots`, `status`, `notes`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clubId,
            $data['title'],
            $data['day_of_week'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time'],
            $data['venue_name'],
            isset($data['venue_maps_url']) ? $data['venue_maps_url'] : null,
            isset($data['court_count']) ? (int)$data['court_count'] : 2,
            isset($data['fee_per_person']) ? (int)$data['fee_per_person'] : 35000,
            isset($data['max_slots']) ? (int)$data['max_slots'] : 16,
            isset($data['status']) ? $data['status'] : 'scheduled',
            isset($data['notes']) ? $data['notes'] : null
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `sessions` SET `status` = ? WHERE `id` = ?");
        return $stmt->execute([$status, $id]);
    }
}
