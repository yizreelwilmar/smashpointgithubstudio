<?php
// shared/Engine/RotationEngine.php
// Enterprise Court Rotation & Matchmaker Engine

class RotationEngine {
    public static function generateRound(
        array $players,
        int $courtCount,
        array $pastMatches = [],
        string $algorithm = 'fair_social'
    ): array {
        $totalPlayers = count($players);
        $slotsNeeded = $courtCount * 4;

        if ($totalPlayers < 4) {
            throw new InvalidArgumentException("Minimal dibutuhkan 4 pemain untuk rotasi ganda.");
        }

        $effectiveCourts = min($courtCount, (int)floor($totalPlayers / 4));
        $effectiveSlots = $effectiveCourts * 4;

        // Current round calculation
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

        // Workload & pairing tracking
        $workload = [];
        $partnerHistory = [];
        $opponentHistory = [];

        foreach ($players as $p) {
            $workload[$p['id']] = [
                'played' => 0,
                'rested' => 0,
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

            self::incrementPairCount($partnerHistory, $tA[0], $tA[1]);
            self::incrementPairCount($partnerHistory, $tB[0], $tB[1]);

            foreach ($tA as $pa) {
                foreach ($tB as $pb) {
                    self::incrementPairCount($opponentHistory, $pa, $pb);
                }
            }

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

        // Sort by fewest games played
        $playerPool = $players;
        usort($playerPool, function ($a, $b) use ($workload) {
            $wA = $workload[$a['id']]['played'];
            $wB = $workload[$b['id']]['played'];
            if ($wA !== $wB) {
                return $wA <=> $wB;
            }
            return mt_rand(-1, 1);
        });

        $selectedPlayers = array_slice($playerPool, 0, $effectiveSlots);
        $restingPlayers = array_slice($playerPool, $effectiveSlots);

        $matches = [];

        if ($algorithm === 'mexicano') {
            usort($selectedPlayers, function ($a, $b) use ($workload) {
                $pA = $workload[$a['id']]['session_points'];
                $pB = $workload[$b['id']]['session_points'];
                if ($pA !== $pB) {
                    return $pB <=> $pA;
                }
                return mt_rand(-1, 1);
            });

            for ($c = 0; $c < $effectiveCourts; $c++) {
                $offset = $c * 4;
                $cPlayers = array_slice($selectedPlayers, $offset, 4);
                $matches[] = [
                    'court' => $c + 1,
                    'court_name' => "Court " . ($c + 1),
                    'team_a' => [$cPlayers[0], $cPlayers[3]],
                    'team_b' => [$cPlayers[1], $cPlayers[2]],
                ];
            }
        } else {
            // 'fair_social'
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

    private static function optimizeSocialPairings(
        array $players,
        int $courtCount,
        array $partnerHistory,
        array $opponentHistory
    ): array {
        $bestScore = PHP_INT_MAX;
        $bestCourts = [];
        $shuffled = $players;

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

                $penalty += (self::getPairCount($partnerHistory, $p1['id'], $p2['id']) * 15);
                $penalty += (self::getPairCount($partnerHistory, $p3['id'], $p4['id']) * 15);

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
                if ($penalty === 0) break;
            }
        }

        return $bestCourts;
    }

    private static function incrementPairCount(array &$history, int $p1, int $p2): void {
        if (!isset($history[$p1])) $history[$p1] = [];
        if (!isset($history[$p2])) $history[$p2] = [];
        $history[$p1][$p2] = (isset($history[$p1][$p2]) ? $history[$p1][$p2] : 0) + 1;
        $history[$p2][$p1] = (isset($history[$p2][$p1]) ? $history[$p2][$p1] : 0) + 1;
    }

    private static function getPairCount(array $history, int $p1, int $p2): int {
        return isset($history[$p1][$p2]) ? $history[$p1][$p2] : 0;
    }
}
