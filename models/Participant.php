<?php
// models/Participant.php
// Session Attendance, Waiting List & Payment Tracking (Flag Lunas / Belum Lunas)

require_once __DIR__ . '/../core/Database.php';

class Participant {
    public static function getBySession(int $sessionId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, p.name, p.level, p.phone, p.total_points, p.wins, p.losses, p.streak
            FROM `session_participants` sp
            JOIN `players` p ON sp.player_id = p.id
            WHERE sp.session_id = ?
            ORDER BY sp.status ASC, sp.is_present DESC, sp.payment_status ASC, p.name ASC
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }

    public static function togglePayment(int $sessionId, int $playerId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `session_participants` 
            SET `payment_status` = IF(`payment_status` = 'paid', 'unpaid', 'paid'),
                `paid_at` = IF(`payment_status` = 'unpaid', NOW(), NULL)
            WHERE `session_id` = ? AND `player_id` = ?
        ");
        return $stmt->execute([$sessionId, $playerId]);
    }

    public static function toggleAttendance(int $sessionId, int $playerId, ?int $forceState = null) {
        $pdo = Database::getConnection();
        if ($forceState !== null) {
            $stmt = $pdo->prepare("
                UPDATE `session_participants` 
                SET `is_present` = ?
                WHERE `session_id` = ? AND `player_id` = ?
            ");
            return $stmt->execute([$forceState ? 1 : 0, $sessionId, $playerId]);
        }
        $stmt = $pdo->prepare("
            UPDATE `session_participants` 
            SET `is_present` = IF(`is_present` = 1, 0, 1)
            WHERE `session_id` = ? AND `player_id` = ?
        ");
        return $stmt->execute([$sessionId, $playerId]);
    }

    public static function add(int $sessionId, int $playerId, string $paymentStatus = 'unpaid', int $amount = 35000, string $status = 'confirmed') {
        $pdo = Database::getConnection();
        $paidAt = ($paymentStatus === 'paid') ? date('Y-m-d H:i:s') : null;
        $isPresent = ($status === 'confirmed') ? 1 : 0;

        $stmt = $pdo->prepare("
            INSERT INTO `session_participants` (`session_id`, `player_id`, `payment_status`, `payment_amount`, `paid_at`, `is_present`, `status`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                `is_present` = VALUES(`is_present`), 
                `status` = VALUES(`status`),
                `payment_status` = IF(`payment_status` = 'paid', 'paid', VALUES(`payment_status`))
        ");
        return $stmt->execute([$sessionId, $playerId, $paymentStatus, $amount, $paidAt, $isPresent, $status]);
    }

    public static function promoteWaitingList(int $sessionId, int $playerId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `session_participants` 
            SET `status` = 'confirmed', `is_present` = 1
            WHERE `session_id` = ? AND `player_id` = ?
        ");
        return $stmt->execute([$sessionId, $playerId]);
    }

    public static function updateStatus(int $sessionId, int $playerId, string $status) {
        $pdo = Database::getConnection();
        $isPresent = ($status === 'confirmed') ? 1 : 0;
        $stmt = $pdo->prepare("
            UPDATE `session_participants` 
            SET `status` = ?, `is_present` = ?
            WHERE `session_id` = ? AND `player_id` = ?
        ");
        return $stmt->execute([$status, $isPresent, $sessionId, $playerId]);
    }

    public static function remove(int $sessionId, int $playerId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `session_participants` WHERE `session_id` = ? AND `player_id` = ?");
        return $stmt->execute([$sessionId, $playerId]);
    }

    /**
     * Import List Nama Pemain dari Format WhatsApp
     * Cerdas mendeteksi bagian Waiting List, Level Pemain, dan Nomor HP
     */
    public static function importFromWhatsappList(int $sessionId, string $rawText, int $fee = 35000) {
        $pdo = Database::getConnection();
        $lines = preg_split('/\r\n|\r|\n/', $rawText);
        $importedNames = [];
        $currentSection = 'confirmed'; // default: langsung masuk ke Pemain Sesi Utama
        $hasMainPlayers = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            // Jika menemukan header List Nama / Peserta Utama, pastikan masuk ke Sesi Utama ('confirmed')
            if (preg_match('/(list\s*nama|daftar\s*pemain|daftar\s*peserta|sesi\s*utama|slot\s*utama|peserta\s*mabar)/iu', $trimmed)) {
                $currentSection = 'confirmed';
                continue;
            }

            // Hanya masuk ke Waiting List jika SUDAH ada pemain di list utama DAN baris ini murni judul seksi Waiting List (bukan kalimat aturan cancel/syarat di header WA)
            $cleanHeader = trim(preg_replace('/[\*\_\~`⏳📋📌🔥•\-:]/u', ' ', $trimmed));
            if ($hasMainPlayers && strlen($cleanHeader) <= 35 && preg_match('/^(waiting\s*list|daftar\s*tunggu|cadangan|wl)(\s*\d*|\s*slot|\s*peserta|\s*pemain)?$/iu', $cleanHeader)) {
                $currentSection = 'waiting_list';
                continue;
            }

            // Pastikan baris adalah list nama bernomor: misal "1. Dori", "2) alv", "8. Setiawan"
            if (!preg_match('/^\s*\d+[\.\)\-]\s*(.*)$/', $trimmed, $matches)) {
                continue;
            }

            $rawCandidate = $matches[1];
            if (empty($rawCandidate)) continue;

            // Deteksi level jika ada tanda kurung atau strip: (Beg/Inter/Adv/Pemula/Mahir)
            $level = 'Intermediate';
            if (preg_match('/[\(\[\-]\s*(pemula|beginner|beg)\s*[\)\]]?/i', $rawCandidate)) {
                $level = 'Beginner';
            } elseif (preg_match('/[\(\[\-]\s*(mahir|advance|adv)\s*[\)\]]?/i', $rawCandidate)) {
                $level = 'Advance';
            } elseif (preg_match('/[\(\[\-]\s*(menengah|intermediate|inter)\s*[\)\]]?/i', $rawCandidate)) {
                $level = 'Intermediate';
            }

            // Deteksi nomor HP jika ada (misal 0812... atau 628...)
            $phone = null;
            if (preg_match('/(08\d{8,12}|628\d{8,12}|\+628\d{8,12})/', $rawCandidate, $phoneMatches)) {
                $phone = $phoneMatches[1];
            }

            // Bersihkan nama dari info level dan nomor HP
            $nameCandidate = preg_replace('/[\(\[\-]\s*(pemula|beginner|beg|mahir|advance|adv|menengah|intermediate|inter)\s*[\)\]]?/i', '', $rawCandidate);
            if ($phone) {
                $nameCandidate = str_replace($phone, '', $nameCandidate);
            }
            $nameCandidate = trim($nameCandidate, " \t\n\r\0\x0B*._-/:;,");

            // Jika hanya titik "." atau kosong (slot belum terisi di WA), abaikan
            if (empty($nameCandidate) || $nameCandidate === '.' || strlen($nameCandidate) < 2) {
                continue;
            }

            // Abaikan jika ternyata baris adalah catatan aturan yang tak sengaja bernomor
            if (stripos($nameCandidate, 'cancel') !== false || stripos($nameCandidate, 'bayar') !== false || stripos($nameCandidate, 'slot') !== false) {
                continue;
            }

            // Cari apakah pemain sudah ada di database (case-insensitive)
            $checkStmt = $pdo->prepare("SELECT id, level FROM `players` WHERE LOWER(`name`) = LOWER(?) LIMIT 1");
            $checkStmt->execute([$nameCandidate]);
            $player = $checkStmt->fetch();

            $playerId = 0;
            if ($player) {
                $playerId = (int)$player['id'];
                // Update level jika berbeda
                if ($level !== 'Intermediate' && $player['level'] !== $level) {
                    $pdo->prepare("UPDATE `players` SET `level` = ? WHERE `id` = ?")->execute([$level, $playerId]);
                }
            } else {
                // Buat pemain baru di database
                $ins = $pdo->prepare("INSERT INTO `players` (`name`, `phone`, `level`, `is_active`) VALUES (?, ?, ?, 1)");
                $ins->execute([$nameCandidate, $phone, $level]);
                $playerId = (int)$pdo->lastInsertId();
            }

            if ($playerId > 0) {
                if ($currentSection === 'confirmed') {
                    $hasMainPlayers = true;
                }
                self::add($sessionId, $playerId, 'unpaid', $fee, $currentSection);
                $importedNames[] = [
                    'name' => $nameCandidate,
                    'level' => $level,
                    'status' => $currentSection
                ];
            }
        }

        return $importedNames;
    }

    public static function getPaymentSummary(int $sessionId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_registered,
                SUM(CASE WHEN is_present = 1 THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN status = 'waiting_list' THEN 1 ELSE 0 END) as total_waiting_list,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as total_confirmed,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as total_paid,
                SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as total_unpaid,
                SUM(CASE WHEN payment_status = 'paid' THEN payment_amount ELSE 0 END) as total_cash_collected,
                SUM(CASE WHEN payment_status = 'unpaid' THEN payment_amount ELSE 0 END) as total_cash_pending
            FROM `session_participants`
            WHERE `session_id` = ?
        ");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row ?: [
            'total_registered' => 0,
            'total_present' => 0,
            'total_waiting_list' => 0,
            'total_confirmed' => 0,
            'total_paid' => 0,
            'total_unpaid' => 0,
            'total_cash_collected' => 0,
            'total_cash_pending' => 0,
        ];
    }
}