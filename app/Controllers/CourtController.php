<?php
// app/Controllers/CourtController.php
// Live Interactive Court Rotation Application Controller (PHP 7.2+ Compatible)

require_once __DIR__ . '/../Models/Session.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/MatchRecord.php';
require_once __DIR__ . '/../Engine/RotationEngine.php';

class CourtController {
    public static function index() {
        $sessionId = (int)(isset($_GET['session_id']) ? $_GET['session_id'] : 0);
        $allSessions = Session::all();

        // Default to the first session if none specified
        if ($sessionId <= 0 && !empty($allSessions)) {
            $sessionId = (int)$allSessions[0]['id'];
        }

        $currentSession = null;
        $participants = [];
        $matches = [];
        $latestRound = 1;
        $activeRoundMatches = [];
        $restingPlayerIds = [];

        if ($sessionId > 0) {
            $currentSession = Session::find($sessionId);
            if ($currentSession) {
                $participants = Session::getParticipants($sessionId);
                $matches = MatchRecord::getBySession($sessionId);

                if (!empty($matches)) {
                    foreach ($matches as $m) {
                        if ($m['round_number'] > $latestRound) {
                            $latestRound = (int)$m['round_number'];
                        }
                    }
                    // Filter matches for the latest round
                    $activeRoundMatches = array_filter($matches, function($m) use ($latestRound) {
                        return $m['round_number'] == $latestRound;
                    });

                    // Find who is resting in this round
                    $playingIds = [];
                    foreach ($activeRoundMatches as $arm) {
                        $playingIds[] = (int)$arm['team_a_p1'];
                        $playingIds[] = (int)$arm['team_a_p2'];
                        $playingIds[] = (int)$arm['team_b_p1'];
                        $playingIds[] = (int)$arm['team_b_p2'];
                    }

                    foreach ($participants as $p) {
                        if ($p['is_present'] == 1 && !in_array((int)$p['player_id'], $playingIds)) {
                            $restingPlayerIds[] = $p;
                        }
                    }
                }
            }
        }

        require __DIR__ . '/../../views/pages/court.php';
    }
}
