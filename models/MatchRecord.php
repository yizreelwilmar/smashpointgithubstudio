<?php
// models/MatchRecord.php
// Matches & Live Scorekeeper (Directly updates player accumulated points)

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/Player.php';

class MatchRecord {
    public static function getBySession(int $sessionId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
                m.score_a as score_team_a,
                m.score_b as score_team_b,
                p1.name as team_a_p1_name, p1.level as team_a_p1_level,
                p2.name as team_a_p2_name, p2.level as team_a_p2_level,
                p3.name as team_b_p1_name, p3.level as team_b_p1_level,
                p4.name as team_b_p2_name, p4.level as team_b_p2_level,
                p1.name as p1_name, p1.level as p1_level,
                p2.name as p2_name, p2.level as p2_level,
                p3.name as p3_name, p3.level as p3_level,
                p4.name as p4_name, p4.level as p4_level
            FROM `matches` m
            JOIN `players` p1 ON m.team_a_p1 = p1.id
            JOIN `players` p2 ON m.team_a_p2 = p2.id
            JOIN `players` p3 ON m.team_b_p1 = p3.id
            JOIN `players` p4 ON m.team_b_p2 = p4.id
            WHERE m.session_id = ?
            ORDER BY m.round_number ASC, m.court_number ASC
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    public static function createBatch(int $sessionId, int $roundNumber, array $matches) {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO `matches` 
                (`session_id`, `round_number`, `court_number`, `team_a_p1`, `team_a_p2`, `team_b_p1`, `team_b_p2`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'playing')
            ");

            foreach ($matches as $m) {
                $stmt->execute([
                    $sessionId,
                    $roundNumber,
                    $m['court'],
                    $m['team_a'][0]['id'],
                    $m['team_a'][1]['id'],
                    $m['team_b'][0]['id'],
                    $m['team_b'][1]['id']
                ]);
            }

            // Mark session as in_progress
            $pdo->prepare("UPDATE `schedules` SET `status` = 'in_progress' WHERE `id` = ? AND `status` = 'open'")
                ->execute([$sessionId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function find(int $matchId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
                m.score_a as score_team_a,
                m.score_b as score_team_b,
                p1.name as team_a_p1_name, p1.level as team_a_p1_level,
                p2.name as team_a_p2_name, p2.level as team_a_p2_level,
                p3.name as team_b_p1_name, p3.level as team_b_p1_level,
                p4.name as team_b_p2_name, p4.level as team_b_p2_level
            FROM `matches` m
            JOIN `players` p1 ON m.team_a_p1 = p1.id
            JOIN `players` p2 ON m.team_a_p2 = p2.id
            JOIN `players` p3 ON m.team_b_p1 = p3.id
            JOIN `players` p4 ON m.team_b_p2 = p4.id
            WHERE m.id = ?
        ");
        $stmt->execute([$matchId]);
        return $stmt->fetch() ?: null;
    }

    public static function saveScore(int $matchId, int $scoreA, int $scoreB) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `matches` WHERE `id` = ?");
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();
        if (!$match) return false;

        $winnerTeam = 'none';
        if ($scoreA > $scoreB) {
            $winnerTeam = 'team_a';
        } elseif ($scoreB > $scoreA) {
            $winnerTeam = 'team_b';
        } else {
            $winnerTeam = 'draw';
        }

        $upd = $pdo->prepare("
            UPDATE `matches` 
            SET `score_a` = ?, `score_b` = ?, `winner_team` = ?, `status` = 'completed', `completed_at` = IF(`completed_at` IS NULL, NOW(), `completed_at`)
            WHERE `id` = ?
        ");
        $res = $upd->execute([$scoreA, $scoreB, $winnerTeam, $matchId]);

        // Always recalculate all player stats dynamically for 100% consistency
        if ($res) {
            Player::recalculateAllStats();
        }

        return $res;
    }

    public static function deleteMatch(int $matchId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `matches` WHERE `id` = ?");
        $res = $stmt->execute([$matchId]);
        if ($res) {
            Player::recalculateAllStats();
        }
        return $res;
    }

    public static function deleteRound(int $sessionId, int $roundNumber) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `matches` WHERE `session_id` = ? AND `round_number` = ?");
        $res = $stmt->execute([$sessionId, $roundNumber]);
        if ($res) {
            Player::recalculateAllStats();
        }
        return $res;
    }

    public static function swapPlayer(int $matchId, string $slot, int $newPlayerId) {
        $allowedSlots = ['team_a_p1', 'team_a_p2', 'team_b_p1', 'team_b_p2'];
        if (!in_array($slot, $allowedSlots)) {
            throw new InvalidArgumentException("Slot pemain tidak valid.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `matches` SET `{$slot}` = ? WHERE `id` = ?");
        $res = $stmt->execute([$newPlayerId, $matchId]);
        if ($res) {
            Player::recalculateAllStats();
        }
        return $res;
    }
}
