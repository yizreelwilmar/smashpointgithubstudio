<?php
// core/RotationEngine.php
// Powershoot Badminton Fair Court Rotation & Level-Balanced Matchmaking Engine

class RotationEngine {
    public static function levelWeight($level): int {
        $lvl = strtolower(trim($level ?: 'intermediate'));
        if (strpos($lvl, 'adv') !== false || strpos($lvl, 'mahir') !== false) {
            return 3;
        }
        if (strpos($lvl, 'beg') !== false || strpos($lvl, 'pemula') !== false) {
            return 1;
        }
        return 2; // Intermediate
    }

    public static function generateRound(
        array $players,
        int $courtCount,
        array $pastMatches = [],
        string $algorithm = 'balanced_level'
    ): array {
        $totalPlayers = count($players);
        $slotsNeeded = $courtCount * 4;

        if ($totalPlayers < 4) {
            throw new InvalidArgumentException("Minimal dibutuhkan 4 pemain untuk rotasi ganda.");
        }

        $effectiveCourts = min($courtCount, (int)floor($totalPlayers / 4));
        $effectiveSlots = $effectiveCourts * 4;

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

        // Prioritize players who have played the FEWEST matches (Fair distribution)
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

        if ($algorithm === 'tiered_level') {
            // Selevel: kelompokkan pemain dengan level sama di lapangan yang sama
            $matches = self::optimizeTieredPairings($selectedPlayers, $effectiveCourts, $partnerHistory, $opponentHistory);
        } elseif ($algorithm === 'fair_social') {
            // Sosial murni acak tanpa bobot level ketat
            $matches = self::optimizeSocialPairings($selectedPlayers, $effectiveCourts, $partnerHistory, $opponentHistory);
        } else {
            // 'balanced_level' (Default & Rekomendasi):
            // Gabungkan level secara silang (Adv + Beg vs Int + Int) agar rating total kedua tim seimbang
            $matches = self::optimizeBalancedLevelPairings($selectedPlayers, $effectiveCourts, $partnerHistory, $opponentHistory);
        }

        // Annotate each match with balance rating info and played_count for transparency
        foreach ($matches as &$mat) {
            $pA1 = &$mat['team_a'][0];
            $pA2 = &$mat['team_a'][1];
            $pB1 = &$mat['team_b'][0];
            $pB2 = &$mat['team_b'][1];

            $pA1['played_count'] = $workload[$pA1['id']]['played'] ?? 0;
            $pA2['played_count'] = $workload[$pA2['id']]['played'] ?? 0;
            $pB1['played_count'] = $workload[$pB1['id']]['played'] ?? 0;
            $pB2['played_count'] = $workload[$pB2['id']]['played'] ?? 0;

            $rA = self::levelWeight($pA1['level']) + self::levelWeight($pA2['level']);
            $rB = self::levelWeight($pB1['level']) + self::levelWeight($pB2['level']);
            $diff = abs($rA - $rB);

            $mat['rating_a'] = $rA;
            $mat['rating_b'] = $rB;
            $mat['rating_diff'] = $diff;

            if ($diff === 0) {
                $mat['balance_label'] = 'Sangat Seimbang (Rating ' . $rA . ' vs ' . $rB . ')';
                $mat['balance_class'] = 'bg-emerald-50 text-emerald-800 border-emerald-200';
            } elseif ($diff === 1) {
                $mat['balance_label'] = 'Seimbang (Rating ' . $rA . ' vs ' . $rB . ')';
                $mat['balance_class'] = 'bg-blue-50 text-blue-800 border-blue-200';
            } else {
                $mat['balance_label'] = 'Selisih +' . $diff . ' (Perlu Intervensi)';
                $mat['balance_class'] = 'bg-amber-50 text-amber-800 border-amber-200';
            }
        }

        foreach ($restingPlayers as &$rp) {
            $rp['played_count'] = $workload[$rp['id']]['played'] ?? 0;
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
     * Generate match for a single empty court using rolling queue dispatch.
     * Takes players available (i.e. present and not currently on any active court),
     * picks the top 4 who have played fewest games, and balances them into Team A vs Team B.
     */
    public static function generateSingleCourt(
        int $courtNumber,
        array $availablePlayers,
        array $pastMatches = [],
        string $algorithm = 'balanced_level'
    ): array {
        if (count($availablePlayers) < 4) {
            throw new InvalidArgumentException("Minimal dibutuhkan 4 pemain bebas (tidak sedang bertanding) untuk mengisi Lapangan {$courtNumber}.");
        }

        // Calculate workload from past matches
        $workload = [];
        $partnerHistory = [];
        $opponentHistory = [];

        foreach ($availablePlayers as $p) {
            $workload[$p['id']] = ['played' => 0];
            $partnerHistory[$p['id']] = [];
            $opponentHistory[$p['id']] = [];
        }

        $courtMaxRound = 0;
        $globalMaxRound = 0;
        foreach ($pastMatches as $m) {
            $tA = [(int)$m['team_a_p1'], (int)$m['team_a_p2']];
            $tB = [(int)$m['team_b_p1'], (int)$m['team_b_p2']];
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

            $rNum = (int)$m['round_number'];
            if ($rNum > $globalMaxRound) {
                $globalMaxRound = $rNum;
            }
            if ((int)$m['court_number'] === $courtNumber && $rNum > $courtMaxRound) {
                $courtMaxRound = $rNum;
            }
        }

        // Sort available players by fewest played matches
        $pool = $availablePlayers;
        usort($pool, function ($a, $b) use ($workload) {
            $wA = $workload[$a['id']]['played'] ?? 0;
            $wB = $workload[$b['id']]['played'] ?? 0;
            if ($wA !== $wB) {
                return $wA <=> $wB;
            }
            return mt_rand(-1, 1);
        });

        $selected = array_slice($pool, 0, 4);
        $remainingResting = array_slice($pool, 4);

        if ($algorithm === 'tiered_level') {
            $matchedCourts = self::optimizeTieredPairings($selected, 1, $partnerHistory, $opponentHistory);
        } elseif ($algorithm === 'fair_social') {
            $matchedCourts = self::optimizeSocialPairings($selected, 1, $partnerHistory, $opponentHistory);
        } else {
            $matchedCourts = self::optimizeBalancedLevelPairings($selected, 1, $partnerHistory, $opponentHistory);
        }

        $match = $matchedCourts[0];
        $match['court'] = $courtNumber;
        $match['court_name'] = "Lapangan " . $courtNumber;
        $nextRound = max($courtMaxRound + 1, $globalMaxRound > 0 ? $globalMaxRound : 1);
        $match['round_number'] = $nextRound;

        $pA1 = &$match['team_a'][0];
        $pA2 = &$match['team_a'][1];
        $pB1 = &$match['team_b'][0];
        $pB2 = &$match['team_b'][1];

        $pA1['played_count'] = $workload[$pA1['id']]['played'] ?? 0;
        $pA2['played_count'] = $workload[$pA2['id']]['played'] ?? 0;
        $pB1['played_count'] = $workload[$pB1['id']]['played'] ?? 0;
        $pB2['played_count'] = $workload[$pB2['id']]['played'] ?? 0;

        $rA = self::levelWeight($pA1['level']) + self::levelWeight($pA2['level']);
        $rB = self::levelWeight($pB1['level']) + self::levelWeight($pB2['level']);
        $diff = abs($rA - $rB);

        $match['rating_a'] = $rA;
        $match['rating_b'] = $rB;
        $match['rating_diff'] = $diff;

        if ($diff === 0) {
            $match['balance_label'] = 'Sangat Seimbang (Rating ' . $rA . ' vs ' . $rB . ')';
            $match['balance_class'] = 'bg-emerald-50 text-emerald-800 border-emerald-200';
        } elseif ($diff === 1) {
            $match['balance_label'] = 'Seimbang (Rating ' . $rA . ' vs ' . $rB . ')';
            $match['balance_class'] = 'bg-blue-50 text-blue-800 border-blue-200';
        } else {
            $match['balance_label'] = 'Selisih +' . $diff . ' (Perlu Intervensi)';
            $match['balance_class'] = 'bg-amber-50 text-amber-800 border-amber-200';
        }

        return [
            'match' => $match,
            'resting' => $remainingResting,
            'selected_players' => $selected
        ];
    }

    /**
     * Balanced Level Optimization (Cross-pairing for maximum competitive equilibrium)
     */
    private static function optimizeBalancedLevelPairings(
        array $players,
        int $courtCount,
        array $partnerHistory,
        array $opponentHistory
    ): array {
        $bestScore = PHP_INT_MAX;
        $bestCourts = [];
        $shuffled = $players;

        $iterations = 200;
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

                // Team ratings
                $w1 = self::levelWeight($p1['level']);
                $w2 = self::levelWeight($p2['level']);
                $w3 = self::levelWeight($p3['level']);
                $w4 = self::levelWeight($p4['level']);

                $ratingA = $w1 + $w2;
                $ratingB = $w3 + $w4;
                $levelDisparity = abs($ratingA - $ratingB);

                // Heavy penalty for skill imbalance (e.g. diff 2 adds 100 points, diff 4 adds 400)
                $penalty += ($levelDisparity * $levelDisparity * 50);

                // History penalties: avoid repeat partners and opponents
                $penalty += (self::getPairCount($partnerHistory, $p1['id'], $p2['id']) * 20);
                $penalty += (self::getPairCount($partnerHistory, $p3['id'], $p4['id']) * 20);

                $penalty += (self::getPairCount($opponentHistory, $p1['id'], $p3['id']) * 5);
                $penalty += (self::getPairCount($opponentHistory, $p1['id'], $p4['id']) * 5);
                $penalty += (self::getPairCount($opponentHistory, $p2['id'], $p3['id']) * 5);
                $penalty += (self::getPairCount($opponentHistory, $p2['id'], $p4['id']) * 5);

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

    /**
     * Tiered Level Pairings (Keep similar skills together)
     */
    private static function optimizeTieredPairings(
        array $players,
        int $courtCount,
        array $partnerHistory,
        array $opponentHistory
    ): array {
        // Sort players by level descending
        $sorted = $players;
        usort($sorted, function($a, $b) {
            $wA = self::levelWeight($a['level']);
            $wB = self::levelWeight($b['level']);
            if ($wA !== $wB) return $wB <=> $wA;
            return mt_rand(-1, 1);
        });

        $courts = [];
        for ($c = 0; $c < $courtCount; $c++) {
            $offset = $c * 4;
            $group = array_slice($sorted, $offset, 4);
            shuffle($group);
            $courts[] = [
                'court' => $c + 1,
                'court_name' => "Court " . ($c + 1),
                'team_a' => [$group[0], $group[1]],
                'team_b' => [$group[2], $group[3]],
            ];
        }
        return $courts;
    }

    /**
     * Social Pairings (Pure Social Diversity)
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

        $iterations = 50;
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

                $penalty += (self::getPairCount($partnerHistory, $p1['id'], $p2['id']) * 20);
                $penalty += (self::getPairCount($partnerHistory, $p3['id'], $p4['id']) * 20);

                $penalty += (self::getPairCount($opponentHistory, $p1['id'], $p3['id']) * 5);
                $penalty += (self::getPairCount($opponentHistory, $p1['id'], $p4['id']) * 5);
                $penalty += (self::getPairCount($opponentHistory, $p2['id'], $p3['id']) * 5);
                $penalty += (self::getPairCount($opponentHistory, $p2['id'], $p4['id']) * 5);

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