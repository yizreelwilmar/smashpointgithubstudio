<?php
// shared/Models/MatchRecord.php
// Match Management & Realtime Scorekeeper Model

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/Player.php';

class MatchRecord {
    public static function getBySession(int $sessionId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
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

            $pdo->prepare("UPDATE `sessions` SET `status` = 'in_progress' WHERE `id` = ? AND `status` = 'scheduled'")
                ->execute([$sessionId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
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

        $wasCompleted = ($match['status'] === 'completed');

        $upd = $pdo->prepare("
            UPDATE `matches` 
            SET `score_a` = ?, `score_b` = ?, `winner_team` = ?, `status` = 'completed', `completed_at` = NOW()
            WHERE `id` = ?
        ");
        $res = $upd->execute([$scoreA, $scoreB, $winnerTeam, $matchId]);

        if (!$wasCompleted && $res) {
            $isTeamAWin = ($winnerTeam === 'team_a');
            $isTeamBWin = ($winnerTeam === 'team_b');

            Player::updateMatchStats((int)$match['team_a_p1'], $isTeamAWin, $scoreA, $scoreB);
            Player::updateMatchStats((int)$match['team_a_p2'], $isTeamAWin, $scoreA, $scoreB);
            Player::updateMatchStats((int)$match['team_b_p1'], $isTeamBWin, $scoreB, $scoreA);
            Player::updateMatchStats((int)$match['team_b_p2'], $isTeamBWin, $scoreB, $scoreA);
        }

        return $res;
    }
}
