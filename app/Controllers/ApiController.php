<?php
// app/Controllers/ApiController.php
// Clean REST API for Live Scoreboard, Rotation & RSVP (PHP 7.2+ Compatible)

require_once __DIR__ . '/../Models/Player.php';
require_once __DIR__ . '/../Models/Session.php';
require_once __DIR__ . '/../Models/MatchRecord.php';
require_once __DIR__ . '/../Engine/RotationEngine.php';

class ApiController {
    public static function handle() {
        header('Content-Type: application/json; charset=utf-8');
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        try {
            switch ($action) {
                case 'generate_round':
                    self::generateRound();
                    break;
                case 'save_score':
                    self::saveScore();
                    break;
                case 'toggle_attendance':
                    self::toggleAttendance();
                    break;
                case 'quick_add_player':
                    self::quickAddPlayer();
                    break;
                case 'rsvp':
                    self::rsvp();
                    break;
                case 'get_session_data':
                    self::getSessionData();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode([
                        'error' => [
                            'code' => 'INVALID_ACTION',
                            'message' => 'Action API tidak dikenal atau belum disediakan.'
                        ]
                    ]);
                    break;
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => $e->getMessage()
                ]
            ]);
        }
        exit;
    }

    private static function getSessionData() {
        $sessionId = (int)(isset($_GET['session_id']) ? $_GET['session_id'] : 0);
        $session = Session::find($sessionId);
        if (!$session) {
            http_response_code(404);
            echo json_encode(['error' => ['code' => 'NOT_FOUND', 'message' => 'Sesi tidak ditemukan.']]);
            return;
        }

        $participants = Session::getParticipants($sessionId);
        $matches = MatchRecord::getBySession($sessionId);

        echo json_encode([
            'data' => [
                'session' => $session,
                'participants' => $participants,
                'matches' => $matches,
            ]
        ]);
    }

    private static function generateRound() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
        $algorithm = isset($_POST['algorithm']) ? $_POST['algorithm'] : 'fair_social';
        $courtCount = (int)(isset($_POST['court_count']) ? $_POST['court_count'] : 0);

        $session = Session::find($sessionId);
        if (!$session) {
            throw new RuntimeException("Sesi mabar tidak ditemukan.");
        }

        if ($courtCount <= 0) {
            $courtCount = (int)$session['court_count'];
        }

        // Get present participants
        $participants = Session::getParticipants($sessionId);
        $presentPlayers = array_filter($participants, function($p) {
            return (int)$p['is_present'] === 1;
        });

        if (count($presentPlayers) < 4) {
            throw new RuntimeException("Minimal dibutuhkan 4 pemain yang hadir untuk memulai rotasi ganda.");
        }

        // Convert to array of player records
        $playerList = array_map(function($p) {
            return [
                'id' => (int)$p['player_id'],
                'name' => $p['name'],
                'level' => $p['level'],
            ];
        }, array_values($presentPlayers));

        // Get past matches of this session
        $pastMatches = MatchRecord::getBySession($sessionId);

        // Check if there are uncompleted matches in current round
        if (!empty($pastMatches)) {
            $maxRound = 0;
            foreach ($pastMatches as $pm) {
                if ($pm['round_number'] > $maxRound) $maxRound = $pm['round_number'];
            }
            $incomplete = array_filter($pastMatches, function($m) use ($maxRound) {
                return $m['round_number'] == $maxRound && $m['status'] != 'completed';
            });
            if (!empty($incomplete)) {
                throw new RuntimeException("Ronde {$maxRound} masih memiliki pertandingan yang belum selesai diinput skornya.");
            }
        }

        $result = RotationEngine::generateRound($playerList, $courtCount, $pastMatches, $algorithm);

        // Save generated matches to database
        MatchRecord::createBatch($sessionId, $result['round_number'], $result['matches']);

        echo json_encode([
            'success' => true,
            'message' => "Ronde {$result['round_number']} berhasil digenerate!",
            'data' => $result
        ]);
    }

    private static function saveScore() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $matchId = (int)(isset($_POST['match_id']) ? $_POST['match_id'] : 0);
        $scoreA = (int)(isset($_POST['score_a']) ? $_POST['score_a'] : 0);
        $scoreB = (int)(isset($_POST['score_b']) ? $_POST['score_b'] : 0);

        if ($scoreA < 0 || $scoreB < 0) {
            throw new RuntimeException("Skor tidak boleh negatif.");
        }

        if ($scoreA === 0 && $scoreB === 0) {
            throw new RuntimeException("Masukkan skor pertandingan yang valid.");
        }

        $saved = MatchRecord::saveScore($matchId, $scoreA, $scoreB);
        if (!$saved) {
            throw new RuntimeException("Gagal menyimpan skor pertandingan.");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Skor pertandingan berhasil dicatat dan leaderboard diperbarui!'
        ]);
    }

    private static function toggleAttendance() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
        $playerId = (int)(isset($_POST['player_id']) ? $_POST['player_id'] : 0);

        Session::toggleAttendance($sessionId, $playerId);

        echo json_encode([
            'success' => true,
            'message' => 'Status kehadiran pemain berhasil diperbarui.'
        ]);
    }

    private static function quickAddPlayer() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $level = isset($_POST['level']) ? $_POST['level'] : 'Intermediate';
        $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');

        if (empty($name)) {
            throw new RuntimeException("Nama pemain wajib diisi.");
        }

        $playerId = Player::create($name, $phone, $level);
        Session::addParticipant($sessionId, $playerId, 'unpaid');

        echo json_encode([
            'success' => true,
            'message' => "Pemain {$name} berhasil ditambahkan ke sesi mabar!",
            'data' => ['player_id' => $playerId, 'name' => $name, 'level' => $level]
        ]);
    }

    private static function rsvp() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }

        $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
        $level = isset($_POST['level']) ? $_POST['level'] : 'Intermediate';

        if (empty($name)) {
            throw new RuntimeException("Nama lengkap wajib diisi untuk reservasi slot.");
        }

        // Check if player exists by name or phone
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM `players` WHERE `name` = ? OR (`phone` IS NOT NULL AND `phone` = ?)");
        $stmt->execute([$name, $phone]);
        $existing = $stmt->fetch();

        if ($existing) {
            $playerId = (int)$existing['id'];
        } else {
            $playerId = Player::create($name, $phone, $level);
        }

        Session::addParticipant($sessionId, $playerId, 'unpaid');

        echo json_encode([
            'success' => true,
            'message' => "Berhasil mendaftar mabar! Slot kamu telah diamankan. Sampai jumpa di lapangan!"
        ]);
    }
}
