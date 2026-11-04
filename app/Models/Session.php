<?php
// app/Models/Session.php
// Weekly Mabar Sessions Model

require_once __DIR__ . '/../../core/Database.php';

class Session {
    public static function all(): array {
        $pdo = Database::getConnection();
        return $pdo->query("
            SELECT s.*, 
                   COUNT(sp.id) as participant_count
            FROM `sessions` s
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            GROUP BY s.id
            ORDER BY s.session_date DESC, s.start_time DESC
        ")->fetchAll();
    }

    public static function upcoming(int $limit = 10): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COUNT(sp.id) as participant_count
            FROM `sessions` s
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            WHERE s.session_date >= CURDATE()
            GROUP BY s.id
            ORDER BY s.session_date ASC, s.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
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
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getParticipants(int $sessionId): array {
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

    public static function addParticipant(int $sessionId, int $playerId, string $paymentStatus = 'unpaid'): bool {
        $pdo = Database::getConnection();
        
        // Check slot quota
        $session = self::find($sessionId);
        if (!$session) return false;

        $currentCount = (int)$session['participant_count'];
        if ($currentCount >= $session['max_slots']) {
            throw new RuntimeException("Sesi mabar sudah penuh! Kuota maksimal {$session['max_slots']} pemain telah tercapai.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO `session_participants` (`session_id`, `player_id`, `payment_status`, `is_present`)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE `is_present` = 1, `payment_status` = VALUES(`payment_status`)
        ");
        return $stmt->execute([$sessionId, $playerId, $paymentStatus]);
    }

    public static function removeParticipant(int $sessionId, int $playerId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `session_participants` WHERE `session_id` = ? AND `player_id` = ?");
        return $stmt->execute([$sessionId, $playerId]);
    }

    public static function toggleAttendance(int $sessionId, int $playerId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `session_participants` 
            SET `is_present` = IF(`is_present` = 1, 0, 1)
            WHERE `session_id` = ? AND `player_id` = ?
        ");
        return $stmt->execute([$sessionId, $playerId]);
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `sessions` 
            (`title`, `day_of_week`, `session_date`, `start_time`, `end_time`, `venue_name`, `venue_maps_url`, `court_count`, `fee_per_person`, `max_slots`, `status`, `notes`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['day_of_week'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time'],
            $data['venue_name'],
            $data['venue_maps_url'] ?? null,
            $data['court_count'] ?? 2,
            $data['fee_per_person'] ?? 35000,
            $data['max_slots'] ?? 16,
            $data['status'] ?? 'scheduled',
            $data['notes'] ?? null
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `sessions` SET `status` = ? WHERE `id` = ?");
        return $stmt->execute([$status, $id]);
    }
}
