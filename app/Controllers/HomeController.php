<?php
// app/Controllers/HomeController.php
// Public Landing Page & Integrated Community Showcase

require_once __DIR__ . '/../Models/Session.php';
require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/Article.php';

class HomeController {
    public static function index(): void {
        $upcomingSessions = Session::upcoming(3);
        $topPlayers = Player::getLeaderboard('all', 5);
        $latestArticles = Article::published(3);

        // Community aggregate statistics
        $pdo = Database::getConnection();
        $totalMatches = (int)$pdo->query("SELECT COUNT(*) FROM `matches` WHERE `status` = 'completed'")->fetchColumn();
        $totalPlayers = (int)$pdo->query("SELECT COUNT(*) FROM `players` WHERE `is_active` = 1")->fetchColumn();
        $totalSessions = (int)$pdo->query("SELECT COUNT(*) FROM `sessions`")->fetchColumn();

        $stats = [
            'matches' => $totalMatches,
            'players' => $totalPlayers,
            'sessions' => $totalSessions,
        ];

        require __DIR__ . '/../../views/pages/home.php';
    }
}
