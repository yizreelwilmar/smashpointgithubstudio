<?php
// app/Engine/RotationEngine.php
// Enterprise Court Rotation & Matchmaker Engine

class RotationEngine {
    /**
     * Generate next round matches based on active players, available courts, and past matches.
     * 
     * @param array $players Array of player records [['id' => 1, 'name' => 'Fajar', 'level' => 'Advance', ...], ...]
     * @param int $courtCount Number of active courts (1, 2, 3, etc.)
     * @param array $pastMatches Array of past matches in this session
     * @param string $algorithm 'fair_social', 'mexicano', 'king_of_court'
     * @return array [
     *    'round_number' => int,
     *    'matches' => [
     *        ['court' => 1, 'team_a' => [$p1, $p2], 'team_b' => [$p3, $p4]],
     *        ...
     *    ],
     *    'resting' => [$p5, $p6, ...],
     *    'stats' => ['active_courts' => int, 'players_playing' => int, 'players_resting' => int]
     * ]
     */
    public static function generateRound(
        array $players,
        int $courtCount,
        array $pastMatches = [],
        string $algorithm = 'fair_social'
    ): array {
        $totalPlayers = count($players);
        $slotsNeeded = $courtCount * 4; // Each double court needs 4 players

        if ($totalPlayers < 4) {
            throw new InvalidArgumentException("Minimal dibutuhkan 4 pemain untuk rotasi ganda.");
        }

        // Adjust court count if players are fewer than capacity
        $effectiveCourts = min($courtCount, (int)floor($totalPlayers / 4));
        $effectiveSlots = $effectiveCourts * 4;

        // Calculate next round number
        $currentRound = 1;
        if (!empty($pastMatches)) {
            $maxRound = 0;
            foreach ($pastMatches as $m) {
                if ($m['round_number'] > $maxRound) {
                    $maxRound = (int)$m['round_number'];
                }
            }
            $currentRound = $maxRound + 1;
        }

        // 1. Calculate player workload (games played & bench count in this session)
        $workload = [];
        $partnerHistory = []; // [p1][p2] => count of times paired
        $opponentHistory = []; // [p1][p2] => count of times played against

        foreach ($players as $p) {
            $workload[$p['id']] = [
                'played' => 0,
                'rested' => 0,
                'last_rested_round' => 0,
                'session_points' => 0,
                'session_wins' => 0
            ];
            $partnerHistory[$p['id']] = [];
            $opponentHistory[$p['id']] = [];
        }

        foreach ($pastMatches as $m) {
            $tA = [$m['team_a_p1'], $m['team_a_p2']];
            $tB = [$m['team_b_p1'], $m['team_b_p2']];
            $allMatchPlayers = array_merge($tA, $tB);

            foreach ($allMatchPlayers as $pId) {
                if (isset($workload[$pId])) {
                    $workload[$pId]['played']++;
                }
            }

            // Record partner synergy
            self::incrementPairCount($partnerHistory, $tA[0], $tA[1]);
            self::incrementPairCount($partnerHistory, $tB[0], $tB[1]);

            // Record opponent encounters
            foreach ($tA as $pa) {
                foreach ($tB as $pb) {
                    self::incrementPairCount($opponentHistory, $pa, $pb);
                }
            }

            // Calculate points & wins if completed
            if ($m['status'] === 'completed') {
                $scoreA = (int)$m['score_a'];
                $scoreB = (int)$m['score_b'];
                if (isset($workload[$tA[0]])) $workload[$tA[0]]['session_points'] += $scoreA;
                if (isset($workload[$tA[1]])) $workload[$tA[1]]['session_points'] += $scoreA;
                if (isset($workload[$tB[0]])) $workload[$tB[0]]['session_points'] += $scoreB;
                if (isset($workload[$tB[1]])) $workload[$tB[1]]['session_points'] += $scoreB;

                if ($m['winner_team'] === 'team_a') {
                    if (isset($workload[$tA[0]])) $workload[$tA[0]]['session_wins']++;
                    if (isset($workload[$tA[1]])) $workload[$tA[1]]['session_wins']++;
                } elseif ($m['winner_team'] === 'team_b') {
                    if (isset($workload[$tB[0]])) $workload[$tB[0]]['session_wins']++;
                    if (isset($workload[$tB[1]])) $workload[$tB[1]]['session_wins']++;
                }
            }
        }

        // 2. Select who plays and who rests
        // Players who have played the FEWEST matches get picked first to play
        // If tied, players with lowest rest count get rested
        $playerPool = $players;
        usort($playerPool, function ($a, $b) use ($workload) {
            $wA = $workload[$a['id']]['played'];
            $wB = $workload[$b['id']]['played'];
            if ($wA !== $wB) {
                return $wA <=> $wB; // Ascending: fewest games played comes first
            }
            // Tie breaker: random jitter to keep it natural
            return mt_rand(-1, 1);
        });

        // Split into selected players for this round and bench players
        $selectedPlayers = array_slice($playerPool, 0, $effectiveSlots);
        $restingPlayers = array_slice($playerPool, $effectiveSlots);

        // 3. Matchmaking & Pairing according to algorithm
        $matches = [];

        if ($algorithm === 'mexicano') {
            // Sort selected players by session points / rank descending
            usort($selectedPlayers, function ($a, $b) use ($workload) {
                $pA = $workload[$a['id']]['session_points'];
                $pB = $workload[$b['id']]['session_points'];
                if ($pA !== $pB) {
                    return $pB <=> $pA; // Descending: top scorers on Court 1
                }
                return mt_rand(-1, 1);
            });

            // Court 1 gets rank 1, 2, 3, 4: Team A (Rank 1 + Rank 4) vs Team B (Rank 2 + Rank 3) for balanced match!
            for ($c = 0; $c < $effectiveCourts; $c++) {
                $offset = $c * 4;
                $cPlayers = array_slice($selectedPlayers, $offset, 4);
                $matches[] = [
                    'court' => $c + 1,
                    'court_name' => "Court " . ($c + 1),
                    'team_a' => [$cPlayers[0], $cPlayers[3]], // Top + Bottom seed of this court
                    'team_b' => [$cPlayers[1], $cPlayers[2]], // Middle seeds
                ];
            }
        } else {
            // Default: 'fair_social'
            // Minimize repeat partnerships and create balanced teams
            $matches = self::optimizeSocialPairings($selectedPlayers, $effectiveCourts, $partnerHistory, $opponentHistory);
        }

        return [
            'round_number' => $currentRound,
            'algorithm' => $algorithm,
            'matches' => $matches,
            'resting' => $restingPlayers,
            'stats' => [
                'total_players' => $totalPlayers,
                'active_courts' => $effectiveCourts,
                'players_playing' => $effectiveSlots,
                'players_resting' => count($restingPlayers),
            ]
        ];
    }

    /**
     * Optimize partnerships to minimize repeat pairs and repeat opponent encounters
     */
    private static function optimizeSocialPairings(
        array $players,
        int $courtCount,
        array $partnerHistory,
        array $opponentHistory
    ): array {
        $bestScore = PHP_INT_MAX;
        $bestCourts = [];
        $shuffled = $players;

        // Perform Monte-Carlo iterations to find the optimal combination that minimizes repeat partner/opponent penalty
        $iterations = 40;
        for ($i = 0; $i < $iterations; $i++) {
            shuffle($shuffled);
            $penalty = 0;
            $currentAttempt = [];

            for ($c = 0; $c < $courtCount; $c++) {
                $offset = $c * 4;
                $p1 = $shuffled[$offset];
                $p2 = $shuffled[$offset + 1];
                $p3 = $shuffled[$offset + 2];
                $p4 = $shuffled[$offset + 3];

                // Partner repeat penalty (high weight)
                $penalty += (self::getPairCount($partnerHistory, $p1['id'], $p2['id']) * 15);
                $penalty += (self::getPairCount($partnerHistory, $p3['id'], $p4['id']) * 15);

                // Opponent repeat penalty (medium weight)
                $penalty += (self::getPairCount($opponentHistory, $p1['id'], $p3['id']) * 4);
                $penalty += (self::getPairCount($opponentHistory, $p1['id'], $p4['id']) * 4);
                $penalty += (self::getPairCount($opponentHistory, $p2['id'], $p3['id']) * 4);
                $penalty += (self::getPairCount($opponentHistory, $p2['id'], $p4['id']) * 4);

                $currentAttempt[] = [
                    'court' => $c + 1,
                    'court_name' => "Court " . ($c + 1),
                    'team_a' => [$p1, $p2],
                    'team_b' => [$p3, $p4],
                ];
            }

            if ($penalty < $bestScore) {
                $bestScore = $penalty;
                $bestCourts = $currentAttempt;
                if ($penalty === 0) {
                    break; // Perfect distribution found
                }
            }
        }

        return $bestCourts;
    }

    private static function incrementPairCount(array &$history, int $p1, int $p2): void {
        if (!isset($history[$p1])) $history[$p1] = [];
        if (!isset($history[$p2])) $history[$p2] = [];
        $history[$p1][$p2] = ($history[$p1][$p2] ?? 0) + 1;
        $history[$p2][$p1] = ($history[$p2][$p1] ?? 0) + 1;
    }

    private static function getPairCount(array $history, int $p1, int $p2): int {
        return $history[$p1][$p2] ?? 0;
    }
}
