<?php
// shared/Models/Club.php
// Multi-Tenant Club / Tenant Model

require_once __DIR__ . '/../core/Database.php';

class Club {
    public static function all() {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `clubs` WHERE `is_active` = 1 ORDER BY name ASC")->fetchAll();
    }

    public static function find(int $id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `clubs` WHERE `id` = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `clubs` WHERE `slug` = ? OR `subdomain` = ?");
        $stmt->execute([$slug, $slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `clubs` (`slug`, `name`, `subdomain`, `sport_type`, `city`, `tagline`, `primary_color`, `whatsapp_contact`, `plan`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['slug'],
            $data['name'],
            $data['subdomain'],
            isset($data['sport_type']) ? $data['sport_type'] : 'Badminton',
            isset($data['city']) ? $data['city'] : 'Jakarta',
            isset($data['tagline']) ? $data['tagline'] : null,
            isset($data['primary_color']) ? $data['primary_color'] : '#10e599',
            isset($data['whatsapp_contact']) ? $data['whatsapp_contact'] : null,
            isset($data['plan']) ? $data['plan'] : 'starter'
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function getStats(int $clubId) {
        $pdo = Database::getConnection();
        $totalPlayers = (int)$pdo->query("SELECT COUNT(*) FROM `players` WHERE `club_id` = {$clubId}")->fetchColumn();
        $totalSessions = (int)$pdo->query("SELECT COUNT(*) FROM `sessions` WHERE `club_id` = {$clubId}")->fetchColumn();
        $totalMatches = (int)$pdo->query("
            SELECT COUNT(m.id) FROM `matches` m
            JOIN `sessions` s ON m.session_id = s.id
            WHERE s.club_id = {$clubId} AND m.status = 'completed'
        ")->fetchColumn();

        return [
            'players' => $totalPlayers,
            'sessions' => $totalSessions,
            'matches' => $totalMatches
        ];
    }
}
