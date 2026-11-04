<?php
// app/Models/MatchRecord.php
// Match Management & Realtime Scorekeeper Model

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/Player.php';

class MatchRecord {
    public static function getBySession(int $sessionId): array {
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

    public static function createBatch(int $sessionId, int $roundNumber, array $matches): void {
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

            // Also update session status to 'in_progress' if scheduled
            $pdo->prepare("UPDATE `sessions` SET `status` = 'in_progress' WHERE `id` = ? AND `status` = 'scheduled'")
                ->execute([$sessionId]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function saveScore(int $matchId, int $scoreA, int $scoreB): bool {
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

        // If match was already completed previously, we revert old score differential before updating
        // For simplicity and accuracy in live matches:
        $wasCompleted = ($match['status'] === 'completed');

        $upd = $pdo->prepare("
            UPDATE `matches` 
            SET `score_a` = ?, `score_b` = ?, `winner_team` = ?, `status` = 'completed', `completed_at` = NOW()
            WHERE `id` = ?
        ");
        $res = $upd->execute([$scoreA, $scoreB, $winnerTeam, $matchId]);

        // If this is the first time completed, update player leaderboard stats
        if (!$wasCompleted && $res) {
            $isTeamAWin = ($winnerTeam === 'team_a');
            $isTeamBWin = ($winnerTeam === 'team_b');

            // Team A
            Player::updateMatchStats($match['team_a_p1'], $isTeamAWin, $scoreA, $scoreB);
            Player::updateMatchStats($match['team_a_p2'], $isTeamAWin, $scoreA, $scoreB);

            // Team B
            Player::updateMatchStats($match['team_b_p1'], $isTeamBWin, $scoreB, $scoreA);
            Player::updateMatchStats($match['team_b_p2'], $isTeamBWin, $scoreB, $scoreA);
        }

        return $res;
    }

    public static function swapPlayer(int $matchId, string $slot, int $newPlayerId): bool {
        // slot: 'team_a_p1', 'team_a_p2', 'team_b_p1', 'team_b_p2'
        $allowedSlots = ['team_a_p1', 'team_a_p2', 'team_b_p1', 'team_b_p2'];
        if (!in_array($slot, $allowedSlots)) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `matches` SET `{$slot}` = ? WHERE `id` = ? AND `status` != 'completed'");
        return $stmt->execute([$newPlayerId, $matchId]);
    }
}
