<?php
// models/Player.php
// Player Statistics & Leaderboard Ranked by Total Accumulated Points

require_once __DIR__ . '/../core/Database.php';

class Player {
    public static function all(bool $activeOnly = true) {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM `players` " . ($activeOnly ? "WHERE `is_active` = 1 " : "") . "ORDER BY `name` ASC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `players` WHERE `id` = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, $phone = null, string $level = 'Intermediate') {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("INSERT INTO `players` (`name`, `phone`, `level`) VALUES (?, ?, ?)");
        $stmt->execute([trim($name), $phone ? trim($phone) : null, $level]);
        return (int)$pdo->lastInsertId();
    }

        public static function update(int $id, string $name, $phone = null, string $level = 'Intermediate') {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `players` SET `name` = ?, `phone` = ?, `level` = ?, `updated_at` = NOW() WHERE `id` = ?");
        return $stmt->execute([trim($name), $phone ? trim($phone) : null, $level, $id]);
    }

    public static function delete(int $id) {
        $pdo = Database::getConnection();
        $stmtP = $pdo->prepare("DELETE FROM `session_participants` WHERE `player_id` = ?");
        $stmtP->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM `players` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    public static function deleteBatch(array $ids) {
        if (empty($ids)) return 0;
        $pdo = Database::getConnection();
        $cleanIds = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));

        $stmtP = $pdo->prepare("DELETE FROM `session_participants` WHERE `player_id` IN ($placeholders)");
        $stmtP->execute($cleanIds);

        $stmt = $pdo->prepare("DELETE FROM `players` WHERE `id` IN ($placeholders)");
        $stmt->execute($cleanIds);
        return $stmt->rowCount();
    }

    public static function getLeaderboard(int $limit = 50) {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                p.*,
                CASE 
                    WHEN p.total_matches > 0 THEN ROUND((p.wins / p.total_matches) * 100, 1)
                    ELSE 0.0 
                END AS win_rate,
                (p.total_points - p.points_conceded) AS point_diff
            FROM `players` p
            WHERE p.is_active = 1
            ORDER BY 
                p.total_points DESC,
                p.wins DESC,
                win_rate DESC,
                p.streak DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Leaderboard of the Month (Filterable by Month & Sesi Mabar)
     */
    public static function getMonthlyLeaderboard($monthYear = null, int $sessionId = 0, int $limit = 50) {
        $pdo = Database::getConnection();
        
        $where = ["m.status = 'completed'"];
        $params = [];

        if (!empty($monthYear)) {
            $where[] = "DATE_FORMAT(m.completed_at, '%Y-%m') = :monthYear";
            $params[':monthYear'] = $monthYear;
        }
        if ($sessionId > 0) {
            $where[] = "m.session_id = :sessionId";
            $params[':sessionId'] = $sessionId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                p.id,
                p.name,
                p.level,
                p.phone,
                COUNT(m.id) as total_matches,
                SUM(CASE 
                    WHEN (m.winner_team = 'team_a' AND (m.team_a_p1 = p.id OR m.team_a_p2 = p.id)) OR
                         (m.winner_team = 'team_b' AND (m.team_b_p1 = p.id OR m.team_b_p2 = p.id))
                    THEN 1 ELSE 0 END) as wins,
                SUM(CASE 
                    WHEN (m.winner_team = 'team_b' AND (m.team_a_p1 = p.id OR m.team_a_p2 = p.id)) OR
                         (m.winner_team = 'team_a' AND (m.team_b_p1 = p.id OR m.team_b_p2 = p.id))
                    THEN 1 ELSE 0 END) as losses,
                SUM(CASE 
                    WHEN m.team_a_p1 = p.id OR m.team_a_p2 = p.id THEN m.score_a
                    WHEN m.team_b_p1 = p.id OR m.team_b_p2 = p.id THEN m.score_b
                    ELSE 0 END) as total_rally_points
            FROM `players` p
            JOIN `matches` m ON (m.team_a_p1 = p.id OR m.team_a_p2 = p.id OR m.team_b_p1 = p.id OR m.team_b_p2 = p.id)
            WHERE $whereClause
            GROUP BY p.id
            ORDER BY total_rally_points DESC, wins DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (empty($results)) {
            if ($sessionId > 0) {
                return [];
            }
            return self::getLeaderboard($limit);
        }

        foreach ($results as &$r) {
            $r['total_points'] = (int)$r['total_rally_points'] + ((int)$r['wins'] * 5);
            $r['win_rate'] = $r['total_matches'] > 0 ? round(($r['wins'] / $r['total_matches']) * 100, 1) : 0;
            $r['streak'] = $r['wins'];
        }
        usort($results, function($a, $b) {
            if ($b['total_points'] !== $a['total_points']) return $b['total_points'] - $a['total_points'];
            return $b['wins'] - $a['wins'];
        });

        return $results;
    }

    public static function updateMatchPoints(int $playerId, int $pointsWon, int $pointsLost, bool $isWin) {
        $pdo = Database::getConnection();
        $curr = self::find($playerId);
        if (!$curr) return;

        $newStreak = $isWin ? (max(0, (int)$curr['streak']) + 1) : 0;
        $winAdd = $isWin ? 1 : 0;
        $lossAdd = $isWin ? 0 : 1;

        $stmt = $pdo->prepare("
            UPDATE `players` 
            SET 
                `total_matches` = `total_matches` + 1,
                `wins` = `wins` + :win,
                `losses` = `losses` + :loss,
                `total_points` = `total_points` + :ptsWon,
                `points_conceded` = `points_conceded` + :ptsLost,
                `streak` = :streak
            WHERE `id` = :id
        ");

        $stmt->execute([
            ':win' => $winAdd,
            ':loss' => $lossAdd,
            ':ptsWon' => $pointsWon,
            ':ptsLost' => $pointsLost,
            ':streak' => $newStreak,
            ':id' => $playerId
        ]);
    }

    /**
     * Engine Perhitungan Leaderboard Musiman ("Banyak-Banyakan Poin" Enterprise)
     * Rumus: Total Poin = (Akumulasi Poin Reli Pertandingan) + (Jumlah Menang × Win Bonus)
     * 
     * @param int $winBonus Bonus poin tambahan untuk setiap kemenangan pertandingan (default 5 poin)
     * @return array Ringkasan hasil kalkulasi
     */
    public static function recalculateAllStats(int $winBonus = 5) {
        $pdo = Database::getConnection();
        
        // 1. Ambil seluruh data pemain aktif
        $players = self::all(false);
        $playerStats = [];
        foreach ($players as $p) {
            $playerStats[(int)$p['id']] = [
                'total_matches' => 0,
                'wins' => 0,
                'losses' => 0,
                'total_points' => 0,
                'points_conceded' => 0,
                'streak' => 0
            ];
        }

        // 2. Ambil seluruh pertandingan yang telah selesai
        $matches = $pdo->query("SELECT * FROM `matches` WHERE `status` = 'completed' ORDER BY `completed_at` ASC, `id` ASC")->fetchAll();

        foreach ($matches as $m) {
            $scoreA = (int)$m['score_a'];
            $scoreB = (int)$m['score_b'];
            $winner = $m['winner_team'];

            $teamA = array_filter([(int)$m['team_a_p1'], (int)$m['team_a_p2']]);
            $teamB = array_filter([(int)$m['team_b_p1'], (int)$m['team_b_p2']]);

            $isAWin = ($winner === 'team_a' || ($scoreA > $scoreB));
            $isBWin = ($winner === 'team_b' || ($scoreB > $scoreA));

            // Proses Team A
            foreach ($teamA as $pId) {
                if (!isset($playerStats[$pId])) continue;
                $playerStats[$pId]['total_matches']++;
                $playerStats[$pId]['points_conceded'] += $scoreB;
                
                if ($isAWin) {
                    $playerStats[$pId]['wins']++;
                    $playerStats[$pId]['total_points'] += ($scoreA + $winBonus);
                    $playerStats[$pId]['streak'] = max(0, $playerStats[$pId]['streak']) + 1;
                } else {
                    $playerStats[$pId]['losses']++;
                    $playerStats[$pId]['total_points'] += $scoreA;
                    $playerStats[$pId]['streak'] = 0;
                }
            }

            // Proses Team B
            foreach ($teamB as $pId) {
                if (!isset($playerStats[$pId])) continue;
                $playerStats[$pId]['total_matches']++;
                $playerStats[$pId]['points_conceded'] += $scoreA;
                
                if ($isBWin) {
                    $playerStats[$pId]['wins']++;
                    $playerStats[$pId]['total_points'] += ($scoreB + $winBonus);
                    $playerStats[$pId]['streak'] = max(0, $playerStats[$pId]['streak']) + 1;
                } else {
                    $playerStats[$pId]['losses']++;
                    $playerStats[$pId]['total_points'] += $scoreB;
                    $playerStats[$pId]['streak'] = 0;
                }
            }
        }

        // 3. Simpan perubahan ke database dengan transaksi
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE `players` 
                SET 
                    `total_matches` = :total_matches,
                    `wins` = :wins,
                    `losses` = :losses,
                    `total_points` = :total_points,
                    `points_conceded` = :points_conceded,
                    `streak` = :streak
                WHERE `id` = :id
            ");

            foreach ($playerStats as $pId => $st) {
                $stmt->execute([
                    ':total_matches' => $st['total_matches'],
                    ':wins' => $st['wins'],
                    ':losses' => $st['losses'],
                    ':total_points' => $st['total_points'],
                    ':points_conceded' => $st['points_conceded'],
                    ':streak' => $st['streak'],
                    ':id' => $pId
                ]);
            }

            $pdo->commit();
            return [
                'total_players' => count($players),
                'total_matches_processed' => count($matches),
                'win_bonus_applied' => $winBonus
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get players with their branch/cabang participation breakdown
     */
    public static function getPlayersWithBranches($filterSessionId = null, $filterBranch = null) {
        $pdo = Database::getConnection();
        
        $sql = "
            SELECT 
                p.*,
                COUNT(DISTINCT sp.session_id) as total_sessions_joined,
                GROUP_CONCAT(DISTINCT s.venue_name ORDER BY s.schedule_date DESC SEPARATOR '||') as branch_venues,
                GROUP_CONCAT(DISTINCT s.branch_area ORDER BY s.schedule_date DESC SEPARATOR '||') as branch_areas,
                GROUP_CONCAT(DISTINCT s.id) as session_ids,
                SUM(CASE WHEN sp.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_sessions,
                SUM(CASE WHEN sp.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_sessions,
                MAX(s.schedule_date) as last_played_date
            FROM `players` p
            LEFT JOIN `session_participants` sp ON p.id = sp.player_id
            LEFT JOIN `schedules` s ON sp.session_id = s.id
        ";

        $where = [];
        $params = [];

        if (!empty($filterSessionId)) {
            $where[] = "sp.session_id = :sessionId";
            $params[':sessionId'] = (int)$filterSessionId;
        }

        if (!empty($filterBranch)) {
            $where[] = "(s.branch_area LIKE :branch OR s.venue_name LIKE :branch)";
            $params[':branch'] = "%{$filterBranch}%";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " GROUP BY p.id ORDER BY total_sessions_joined DESC, p.total_points DESC, p.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Penyesuaian / Koreksi Poin Manual oleh Admin
     */
    public static function adjustPoints(int $playerId, int $pointsDelta, string $reason = '') {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `players` 
            SET `total_points` = GREATEST(0, `total_points` + :delta)
            WHERE `id` = :id
        ");
        return $stmt->execute([
            ':delta' => $pointsDelta,
            ':id' => $playerId
        ]);
    }
}
