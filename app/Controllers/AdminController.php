<?php
// app/Controllers/AdminController.php
// CMS Admin Management Controller

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../Models/Session.php';
require_once __DIR__ . '/../Models/Article.php';
require_once __DIR__ . '/../Models/Player.php';

class AdminController {
    public static function login(): void {
        if (Auth::check()) {
            header("Location: index.php?page=admin");
            exit;
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = trim($_POST['username'] ?? '');
            $pass = trim($_POST['password'] ?? '');
            if (Auth::attempt($user, $pass)) {
                header("Location: index.php?page=admin");
                exit;
            } else {
                $error = "Username atau password salah.";
            }
        }
        require __DIR__ . '/../../views/admin/login.php';
    }

    public static function logout(): void {
        Auth::logout();
        header("Location: index.php?page=admin-login");
        exit;
    }

    public static function dashboard(): void {
        Auth::requireAdmin();
        $sessions = Session::all();
        $articles = Article::all();
        $players = Player::all(false);

        $pdo = Database::getConnection();
        $totalMatches = (int)$pdo->query("SELECT COUNT(*) FROM `matches`")->fetchColumn();

        require __DIR__ . '/../../views/admin/dashboard.php';
    }

    public static function sessions(): void {
        Auth::requireAdmin();
        $success = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                try {
                    Session::create([
                        'title' => trim($_POST['title']),
                        'day_of_week' => $_POST['day_of_week'],
                        'session_date' => $_POST['session_date'],
                        'start_time' => $_POST['start_time'],
                        'end_time' => $_POST['end_time'],
                        'venue_name' => trim($_POST['venue_name']),
                        'venue_maps_url' => trim($_POST['venue_maps_url'] ?? ''),
                        'court_count' => (int)$_POST['court_count'],
                        'fee_per_person' => (int)$_POST['fee_per_person'],
                        'max_slots' => (int)$_POST['max_slots'],
                        'status' => $_POST['status'] ?? 'scheduled',
                        'notes' => trim($_POST['notes'] ?? ''),
                    ]);
                    $success = "Sesi mabar baru berhasil ditambahkan!";
                } catch (Exception $e) {
                    $error = "Gagal membuat sesi: " . $e->getMessage();
                }
            } elseif ($action === 'update_status') {
                Session::updateStatus((int)$_POST['session_id'], $_POST['status']);
                $success = "Status sesi diperbarui!";
            }
        }

        $sessions = Session::all();
        require __DIR__ . '/../../views/admin/sessions.php';
    }

    public static function articles(): void {
        Auth::requireAdmin();
        $success = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'create';
            if ($action === 'create') {
                try {
                    Article::create([
                        'title' => trim($_POST['title']),
                        'category' => $_POST['category'] ?? 'Pengumuman',
                        'excerpt' => trim($_POST['excerpt']),
                        'content' => trim($_POST['content']),
                        'cover_image' => trim($_POST['cover_image'] ?? ''),
                        'is_published' => isset($_POST['is_published']) ? 1 : 0,
                    ]);
                    $success = "Artikel / Pengumuman baru berhasil diterbitkan!";
                } catch (Exception $e) {
                    $error = "Gagal menerbitkan artikel: " . $e->getMessage();
                }
            } elseif ($action === 'delete') {
                Article::delete((int)$_POST['id']);
                $success = "Artikel berhasil dihapus.";
            }
        }

        $articles = Article::all();
        require __DIR__ . '/../../views/admin/articles.php';
    }

    public static function players(): void {
        Auth::requireAdmin();
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                Player::create($_POST['name'], $_POST['phone'] ?? null, $_POST['level'] ?? 'Intermediate');
                $success = "Pemain baru berhasil ditambahkan.";
            } elseif ($action === 'reset_leaderboard') {
                $pdo = Database::getConnection();
                $pdo->exec("UPDATE `players` SET `total_matches` = 0, `wins` = 0, `losses` = 0, `total_points_won` = 0, `total_points_lost` = 0, `streak` = 0");
                $success = "Leaderboard berhasil direset untuk musim baru!";
            }
        }

        $players = Player::all(false);
        require __DIR__ . '/../../views/admin/players.php';
    }
}
