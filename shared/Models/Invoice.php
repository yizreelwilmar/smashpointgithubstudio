<?php
// shared/Models/Invoice.php
// Payment Gateway Ready Invoice Model (Midtrans / Xendit / Tripay ready)

require_once __DIR__ . '/../core/Database.php';

class Invoice {
    public static function create(int $clubId, int $amount, ?int $sessionId = null, ?int $playerId = null, string $method = 'manual_transfer') {
        $pdo = Database::getConnection();
        $invNo = 'INV-' . strtoupper(substr(uniqid(), -6)) . '-' . date('ymd');

        $stmt = $pdo->prepare("
            INSERT INTO `invoices` (`club_id`, `session_id`, `player_id`, `invoice_number`, `amount`, `payment_method`, `payment_status`)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$clubId, $sessionId, $playerId, $invNo, $amount, $method]);
        return [
            'id' => (int)$pdo->lastInsertId(),
            'invoice_number' => $invNo,
            'amount' => $amount,
            'status' => 'pending'
        ];
    }

    public static function markPaid(string $invoiceNumber) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `invoices` SET `payment_status` = 'paid', `paid_at` = NOW() WHERE `invoice_number` = ?");
        return $stmt->execute([$invoiceNumber]);
    }
}
