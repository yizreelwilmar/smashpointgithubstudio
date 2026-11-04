<?php
// app/Controllers/LeaderboardController.php
// Public Leaderboard & Hall of Fame Controller

require_once __DIR__ . '/../Models/Player.php';

class LeaderboardController {
    public static function index(): void {
        $leaderboard = Player::getLeaderboard('all', 100);
        require __DIR__ . '/../../views/pages/leaderboard.php';
    }
}
