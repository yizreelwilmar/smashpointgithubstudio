<?php
// api/index.php
// Powershoot Badminton Management System API & Courtside Rotations

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/RotationEngine.php';
require_once __DIR__ . '/../models/Schedule.php';
require_once __DIR__ . '/../models/Player.php';
require_once __DIR__ . '/../models/Participant.php';
require_once __DIR__ . '/../models/MatchRecord.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Parse JSON payload if sent via application/json
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonInput = json_decode($rawInput, true);
    if (is_array($jsonInput)) {
        $_POST = array_merge($_POST, $jsonInput);
    }
}

// Helper: Validate that session is active and not completed
function checkSessionActive(?int $sessionId): array {
    if (!$sessionId || $sessionId <= 0) {
        throw new RuntimeException("Sesi mabar tidak valid.");
    }
    $session = Schedule::find($sessionId);
    if (!$session) {
        throw new RuntimeException("Sesi mabar tidak ditemukan.");
    }
    if (($session['status'] ?? '') === 'completed') {
        throw new RuntimeException("Sesi mabar ini telah selesai dan dikunci. Pertandingan, pemain, dan rotasi tidak dapat diubah lagi.");
    }
    return $session;
}

function checkMatchSessionActive(int $matchId): array {
    if ($matchId <= 0) throw new RuntimeException("ID pertandingan tidak valid.");
    $match = MatchRecord::find($matchId);
    if (!$match) throw new RuntimeException("Data pertandingan tidak ditemukan.");
    $sessId = (int)($match['session_id'] ?? 0);
    if ($sessId > 0) {
        $session = Schedule::find($sessId);
        if ($session && ($session['status'] ?? '') === 'completed') {
            throw new RuntimeException("Sesi mabar ini telah selesai dan dikunci. Pertandingan tidak dapat diubah lagi.");
        }
    }
    return $match;
}

try {
    switch ($action) {
        case 'get_leaderboard':
            $rawSessionParam = isset($_GET['session']) ? $_GET['session'] : (isset($_POST['session']) ? $_POST['session'] : (isset($_GET['session_id']) ? $_GET['session_id'] : (isset($_POST['session_id']) ? $_POST['session_id'] : 0)));
            $sessionId = is_string($rawSessionParam) && !is_numeric($rawSessionParam) ? Security::decodeId($rawSessionParam) : (int)$rawSessionParam;
            $limit = (int)(isset($_GET['limit']) ? $_GET['limit'] : 25);

            if ($sessionId > 0) {
                $board = Player::getMonthlyLeaderboard(null, $sessionId, $limit);
            } else {
                $board = Player::getLeaderboard($limit);
            }

            echo json_encode([
                'success' => true,
                'session_id' => $sessionId,
                'data' => $board
            ]);
            break;

        case 'generate_round':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $algorithm = isset($_POST['algorithm']) ? $_POST['algorithm'] : 'balanced_level';
            $courtCount = (int)(isset($_POST['court_count']) ? $_POST['court_count'] : 0);

            $session = checkSessionActive($sessionId);
            if ($courtCount <= 0) $courtCount = (int)$session['court_count'];

            $participants = Participant::getBySession($sessionId);
            $presentPlayers = array_filter($participants, function($p) {
                return (int)$p['is_present'] === 1;
            });

            if (count($presentPlayers) < 4) {
                throw new RuntimeException("Minimal dibutuhkan 4 pemain hadir untuk rotasi ganda.");
            }

            $playerList = array_map(function($p) {
                return [
                    'id' => (int)$p['player_id'],
                    'name' => $p['name'],
                    'level' => $p['level'],
                ];
            }, array_values($presentPlayers));

            $pastMatches = MatchRecord::getBySession($sessionId);

            $result = RotationEngine::generateRound($playerList, $courtCount, $pastMatches, $algorithm);
            MatchRecord::createBatch($sessionId, $result['round_number'], $result['matches']);

            echo json_encode([
                'success' => true,
                'message' => "Ronde {$result['round_number']} berhasil disusun!",
                'data' => $result
            ]);
            break;

        case 'generate_single_court':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $courtNumber = (int)(isset($_POST['court_number']) ? $_POST['court_number'] : 1);
            $algorithm = isset($_POST['algorithm']) ? $_POST['algorithm'] : 'balanced_level';

            $session = checkSessionActive($sessionId);

            $pastMatches = MatchRecord::getBySession($sessionId);

            // Check if this court already has an ongoing match
            foreach ($pastMatches as $pm) {
                if ((int)$pm['court_number'] === $courtNumber && $pm['status'] === 'playing') {
                    throw new RuntimeException("Lapangan {$courtNumber} masih memiliki pertandingan aktif. Simpan skor terlebih dahulu.");
                }
            }

            // Collect player IDs currently playing on other active courts
            $busyPlayerIds = [];
            foreach ($pastMatches as $pm) {
                if ($pm['status'] === 'playing') {
                    $busyPlayerIds[] = (int)$pm['team_a_p1'];
                    $busyPlayerIds[] = (int)$pm['team_a_p2'];
                    $busyPlayerIds[] = (int)$pm['team_b_p1'];
                    $busyPlayerIds[] = (int)$pm['team_b_p2'];
                }
            }
            $busyPlayerIds = array_unique($busyPlayerIds);

            // Present participants who are NOT currently playing
            $participants = Participant::getBySession($sessionId);
            $availableParticipants = array_filter($participants, function($p) use ($busyPlayerIds) {
                return (int)$p['is_present'] === 1 && !in_array((int)$p['player_id'], $busyPlayerIds);
            });

            if (count($availableParticipants) < 4) {
                $rem = count($availableParticipants);
                throw new RuntimeException("Hanya ada {$rem} pemain menganggur yang hadir. Minimal dibutuhkan 4 pemain bebas untuk mengisi Lapangan {$courtNumber}.");
            }

            $availablePlayerList = array_map(function($p) {
                return [
                    'id' => (int)$p['player_id'],
                    'name' => $p['name'],
                    'level' => $p['level'],
                ];
            }, array_values($availableParticipants));

            $res = RotationEngine::generateSingleCourt($courtNumber, $availablePlayerList, $pastMatches, $algorithm);
            $m = $res['match'];

            $newMatchId = MatchRecord::createSingle(
                $sessionId,
                (int)$m['round_number'],
                $courtNumber,
                (int)$m['team_a'][0]['id'],
                (int)$m['team_a'][1]['id'],
                (int)$m['team_b'][0]['id'],
                (int)$m['team_b'][1]['id']
            );

            echo json_encode([
                'success' => true,
                'message' => "Lapangan {$courtNumber} siap! 4 pemain berikutnya berhasil dipanggil.",
                'match_id' => $newMatchId,
                'data' => $res
            ]);
            break;

        case 'save_score':
            $matchId = (int)(isset($_POST['match_id']) ? $_POST['match_id'] : 0);
            $scoreA = (int)(isset($_POST['score_a']) ? $_POST['score_a'] : 0);
            $scoreB = (int)(isset($_POST['score_b']) ? $_POST['score_b'] : 0);

            if ($scoreA < 0 || $scoreB < 0 || ($scoreA === 0 && $scoreB === 0)) {
                throw new RuntimeException("Masukkan angka skor yang valid.");
            }

            checkMatchSessionActive($matchId);

            $saved = MatchRecord::saveScore($matchId, $scoreA, $scoreB);
            if (!$saved) throw new RuntimeException("Gagal menyimpan skor pertandingan.");

            echo json_encode([
                'success' => true,
                'message' => "Skor terekam! Poin dan leaderboard otomatis diperbarui."
            ]);
            break;

        case 'delete_match':
            $matchId = (int)(isset($_POST['match_id']) ? $_POST['match_id'] : 0);
            checkMatchSessionActive($matchId);

            MatchRecord::deleteMatch($matchId);
            echo json_encode([
                'success' => true,
                'message' => "Pertandingan berhasil dihapus dari rotasi."
            ]);
            break;

        case 'delete_round':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $roundNumber = (int)(isset($_POST['round_number']) ? $_POST['round_number'] : 0);

            if ($roundNumber <= 0) {
                throw new RuntimeException("Nomor ronde tidak valid.");
            }

            checkSessionActive($sessionId);

            MatchRecord::deleteRound($sessionId, $roundNumber);
            echo json_encode([
                'success' => true,
                'message' => "Ronde #{$roundNumber} berhasil dibatalkan dan dihapus."
            ]);
            break;

        case 'swap_match_player':
            $matchId = (int)(isset($_POST['match_id']) ? $_POST['match_id'] : 0);
            $slot = trim(isset($_POST['slot']) ? $_POST['slot'] : '');
            $newPlayerId = (int)(isset($_POST['new_player_id']) ? $_POST['new_player_id'] : 0);

            if (empty($slot) || $newPlayerId <= 0) {
                throw new RuntimeException("Data penukaran pemain tidak lengkap.");
            }

            checkMatchSessionActive($matchId);

            MatchRecord::swapPlayer($matchId, $slot, $newPlayerId);
            echo json_encode([
                'success' => true,
                'message' => "Pemain berhasil ditukar di lapangan!"
            ]);
            break;

        case 'edit_player':
            $playerId = (int)(isset($_POST['player_id']) ? $_POST['player_id'] : 0);
            $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
            $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
            $level = isset($_POST['level']) ? $_POST['level'] : 'Intermediate';
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $status = isset($_POST['status']) ? $_POST['status'] : null;

            if ($playerId <= 0 || empty($name)) {
                throw new RuntimeException("Nama pemain wajib diisi.");
            }

            if ($sessionId > 0) {
                checkSessionActive($sessionId);
            }

            Player::update($playerId, $name, $phone, $level);

            if ($sessionId > 0 && in_array($status, ['confirmed', 'waiting_list'])) {
                Participant::updateStatus($sessionId, $playerId, $status);
            }

            echo json_encode([
                'success' => true,
                'message' => "Data pemain berhasil diperbarui!"
            ]);
            break;

        case 'promote_waiting_list':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $playerId = (int)(isset($_POST['player_id']) ? $_POST['player_id'] : 0);

            if ($playerId <= 0) {
                throw new RuntimeException("Data pemain tidak valid.");
            }

            checkSessionActive($sessionId);

            Participant::promoteWaitingList($sessionId, $playerId);
            echo json_encode([
                'success' => true,
                'message' => "Pemain berhasil dipromosikan ke Sesi Utama!"
            ]);
            break;

        case 'remove_participant':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $playerId = (int)(isset($_POST['player_id']) ? $_POST['player_id'] : 0);

            if ($playerId <= 0) {
                throw new RuntimeException("Data pemain tidak valid.");
            }

            checkSessionActive($sessionId);

            Participant::remove($sessionId, $playerId);
            echo json_encode([
                'success' => true,
                'message' => "Pemain berhasil dihapus dari sesi mabar."
            ]);
            break;

        case 'import_whatsapp':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $rawText = trim(isset($_POST['raw_text']) ? $_POST['raw_text'] : '');
            $sess = checkSessionActive($sessionId);
            $fee = $sess ? (int)$sess['fee_per_person'] : 35000;

            if (empty($rawText)) {
                throw new RuntimeException("Tempelkan (paste) teks list WhatsApp terlebih dahulu.");
            }

            $imported = Participant::importFromWhatsappList($sessionId, $rawText, $fee);
            $count = count($imported);

            if ($count === 0) {
                throw new RuntimeException("Tidak ada nama pemain bernomor yang berhasil dideteksi. Pastikan format teks seperti '1. Budi (Inter)'");
            }

            echo json_encode([
                'success' => true,
                'message' => "Berhasil mengimpor {$count} pemain dari list WhatsApp!",
                'imported_count' => $count,
                'data' => $imported
            ]);
            break;

        case 'toggle_payment':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $playerId = (int)(isset($_POST['player_id']) ? $_POST['player_id'] : 0);

            checkSessionActive($sessionId);

            Participant::togglePayment($sessionId, $playerId);
            $summary = Participant::getPaymentSummary($sessionId);

            echo json_encode([
                'success' => true,
                'message' => 'Status pembayaran berhasil diubah!',
                'summary' => $summary
            ]);
            break;

        case 'toggle_attendance':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $playerId = (int)(isset($_POST['player_id']) ? $_POST['player_id'] : 0);
            $forceState = isset($_POST['is_present']) && $_POST['is_present'] !== '' ? (int)$_POST['is_present'] : null;

            checkSessionActive($sessionId);

            Participant::toggleAttendance($sessionId, $playerId, $forceState);
            $summary = Participant::getPaymentSummary($sessionId);
            echo json_encode([
                'success' => true,
                'message' => 'Status kehadiran pemain berhasil diperbarui.',
                'summary' => $summary
            ]);
            break;

        case 'quick_add_player':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
            $level = isset($_POST['level']) ? $_POST['level'] : 'Intermediate';
            $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
            $status = isset($_POST['status']) && in_array($_POST['status'], ['confirmed', 'waiting_list']) ? $_POST['status'] : 'confirmed';

            if (empty($name)) throw new RuntimeException("Nama pemain wajib diisi.");

            $sess = checkSessionActive($sessionId);
            $playerId = Player::create($name, $phone, $level);
            $fee = $sess ? (int)$sess['fee_per_person'] : 35000;

            Participant::add($sessionId, $playerId, 'unpaid', $fee, $status);

            echo json_encode([
                'success' => true,
                'message' => "Pemain {$name} berhasil didaftarkan (" . ($status === 'waiting_list' ? 'Waiting List' : 'Pemain Utama') . ")!",
                'data' => ['player_id' => $playerId, 'name' => $name, 'level' => $level, 'status' => $status]
            ]);
            break;

        case 'rsvp':
            $sessionId = (int)(isset($_POST['session_id']) ? $_POST['session_id'] : 0);
            $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
            $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
            $level = isset($_POST['level']) ? $_POST['level'] : 'Intermediate';

            if (empty($name)) throw new RuntimeException("Nama lengkap wajib diisi untuk daftar slot.");

            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id FROM `players` WHERE `name` = ? OR (`phone` IS NOT NULL AND `phone` = ?)");
            $stmt->execute([$name, $phone]);
            $existing = $stmt->fetch();

            if ($existing) {
                $playerId = (int)$existing['id'];
            } else {
                $playerId = Player::create($name, $phone, $level);
            }

            $sess = Schedule::find($sessionId);
            $fee = $sess ? (int)$sess['fee_per_person'] : 35000;

            Participant::add($sessionId, $playerId, 'unpaid', $fee, 'confirmed');

            echo json_encode([
                'success' => true,
                'message' => "Berhasil mendaftar mabar! Slot kamu telah diamankan. Silakan konfirmasi via WhatsApp ke PIC sesi."
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => ['code' => 'INVALID_ACTION', 'message' => 'Action tidak valid.']]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => ['code' => 'ERROR', 'message' => $e->getMessage()]]);
}
exit;