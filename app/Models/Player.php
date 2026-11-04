<?php
// app/Models/Player.php
// Enterprise Player & Leaderboard Model

require_once __DIR__ . '/../../core/Database.php';

class Player {
    public static function all(bool $activeOnly = true): array {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM `players` " . ($activeOnly ? "WHERE `is_active` = 1 " : "") . "ORDER BY `name` ASC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `players` WHERE `id` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getLeaderboard(string $scope = 'all', int $limit = 50): array {
        $pdo = Database::getConnection();
        
        // Calculated fields: win_rate (%), point_diff
        $sql = "
            SELECT 
                p.*,
                CASE 
                    WHEN p.total_matches > 0 THEN ROUND((p.wins / p.total_matches) * 100, 1)
                    ELSE 0.0 
                END AS win_rate,
                (p.total_points_won - p.total_points_lost) AS point_diff
            FROM `players` p
            WHERE p.is_active = 1
            ORDER BY 
                p.wins DESC,
                win_rate DESC,
                point_diff DESC,
                p.total_points_won DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(string $name, ?string $phone, string $level = 'Intermediate'): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `players` (`name`, `phone`, `level`)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([trim($name), $phone ? trim($phone) : null, $level]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateMatchStats(int $playerId, bool $isWin, int $ptsWon, int $ptsLost): void {
        $pdo = Database::getConnection();
        
        // Fetch current streak
        $curr = self::find($playerId);
        if (!$curr) return;

        $newStreak = $isWin ? (max(0, $curr['streak']) + 1) : 0;
        $winAdd = $isWin ? 1 : 0;
        $lossAdd = $isWin ? 0 : 1;

        $stmt = $pdo->prepare("
            UPDATE `players` 
            SET 
                `total_matches` = `total_matches` + 1,
                `wins` = `wins` + :win,
                `losses` = `losses` + :loss,
                `total_points_won` = `total_points_won` + :ptsWon,
                `total_points_lost` = `total_points_lost` + :ptsLost,
                `streak` = :streak
            WHERE `id` = :id
        ");

        $stmt->execute([
            ':win' => $winAdd,
            ':loss' => $lossAdd,
            ':ptsWon' => $ptsWon,
            ':ptsLost' => $ptsLost,
            ':streak' => $newStreak,
            ':id' => $playerId
        ]);
    }
}
