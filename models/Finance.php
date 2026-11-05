<?php
// models/Finance.php
// Financial Management & Cashflow Model for Powershoot Badminton Club

require_once __DIR__ . '/../core/Database.php';

class Finance {
    /**
     * Get list of financial records with optional filters
     */
    public static function getAll(array $filters = []): array {
        $pdo = Database::getConnection();
        $where = [];
        $params = [];

        if (!empty($filters['session_id'])) {
            $where[] = "fr.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }

        if (!empty($filters['type'])) {
            $where[] = "fr.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['category'])) {
            $where[] = "fr.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['month'])) {
            $where[] = "DATE_FORMAT(fr.record_date, '%Y-%m') = ?";
            $params[] = $filters['month'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT fr.*, 
                   s.day_name, s.venue_name, s.schedule_date,
                   adm.name as creator_name
            FROM `financial_records` fr
            LEFT JOIN `schedules` s ON fr.session_id = s.id
            LEFT JOIN `admins` adm ON fr.created_by = adm.id
            {$whereClause}
            ORDER BY fr.record_date DESC, fr.id DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new financial transaction record
     */
    public static function create(array $data): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `financial_records` 
            (`session_id`, `type`, `category`, `amount`, `title`, `notes`, `record_date`, `created_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            !empty($data['session_id']) ? (int)$data['session_id'] : null,
            $data['type'] ?? 'expense',
            $data['category'] ?? 'Lain-lain',
            (int)($data['amount'] ?? 0),
            $data['title'] ?? 'Pengeluaran Operasional',
            $data['notes'] ?? null,
            !empty($data['record_date']) ? $data['record_date'] : date('Y-m-d'),
            !empty($data['created_by']) ? (int)$data['created_by'] : null
        ]);
    }

    /**
     * Delete financial record by ID
     */
    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `financial_records` WHERE `id` = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get high-level financial summary KPIs
     */
    public static function getSummary(int $sessionId = 0, string $month = ''): array {
        $pdo = Database::getConnection();

        // 1. Participant fee calculations (from session_participants)
        $pWhere = ["1=1"];
        $pParams = [];

        if ($sessionId > 0) {
            $pWhere[] = "sp.session_id = ?";
            $pParams[] = $sessionId;
        }

        if (!empty($month)) {
            $pWhere[] = "DATE_FORMAT(s.schedule_date, '%Y-%m') = ?";
            $pParams[] = $month;
        }

        $pWhereSql = implode(" AND ", $pWhere);
        $pStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN sp.payment_status = 'paid' THEN sp.payment_amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN sp.payment_status = 'unpaid' THEN sp.payment_amount ELSE 0 END), 0) as total_unpaid,
                COUNT(sp.id) as total_participants,
                SUM(CASE WHEN sp.payment_status = 'paid' THEN 1 ELSE 0 END) as count_paid,
                SUM(CASE WHEN sp.payment_status = 'unpaid' THEN 1 ELSE 0 END) as count_unpaid
            FROM `session_participants` sp
            JOIN `schedules` s ON sp.session_id = s.id
            WHERE {$pWhereSql}
        ");
        $pStmt->execute($pParams);
        $partStats = $pStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_paid' => 0, 'total_unpaid' => 0, 'total_participants' => 0, 'count_paid' => 0, 'count_unpaid' => 0
        ];

        // 2. Extra income and expenses from financial_records
        $fWhere = [];
        $fParams = [];

        if ($sessionId > 0) {
            $fWhere[] = "fr.session_id = ?";
            $fParams[] = $sessionId;
        }

        if (!empty($month)) {
            $fWhere[] = "DATE_FORMAT(fr.record_date, '%Y-%m') = ?";
            $fParams[] = $month;
        }

        $fWhereSql = !empty($fWhere) ? "WHERE " . implode(" AND ", $fWhere) : "";
        $fStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN fr.type = 'expense' THEN fr.amount ELSE 0 END), 0) as total_expense,
                COALESCE(SUM(CASE WHEN fr.type = 'income' THEN fr.amount ELSE 0 END), 0) as total_extra_income
            FROM `financial_records` fr
            {$fWhereSql}
        ");
        $fStmt->execute($fParams);
        $finStats = $fStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_expense' => 0, 'total_extra_income' => 0];

        $totalIncome = (int)$partStats['total_paid'] + (int)$finStats['total_extra_income'];
        $totalExpense = (int)$finStats['total_expense'];
        $netCashflow = $totalIncome - $totalExpense;

        return [
            'total_iuran_collected' => (int)$partStats['total_paid'],
            'total_iuran_pending'   => (int)$partStats['total_unpaid'],
            'count_paid'            => (int)$partStats['count_paid'],
            'count_unpaid'          => (int)$partStats['count_unpaid'],
            'total_extra_income'    => (int)$finStats['total_extra_income'],
            'total_income'          => $totalIncome,
            'total_expense'         => $totalExpense,
            'net_cashflow'          => $netCashflow
        ];
    }

    /**
     * Get breakdown of P&L per mabar session
     */
    public static function getSessionBreakdown(int $limit = 15): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT 
                s.id as session_id,
                s.day_name,
                s.venue_name,
                s.schedule_date,
                s.court_count,
                s.fee_per_person,
                s.status,
                COUNT(sp.id) as total_players,
                COALESCE(SUM(CASE WHEN sp.payment_status = 'paid' THEN sp.payment_amount ELSE 0 END), 0) as income_iuran,
                COALESCE(SUM(CASE WHEN sp.payment_status = 'unpaid' THEN sp.payment_amount ELSE 0 END), 0) as pending_iuran,
                COALESCE(fin.total_session_expense, 0) as expense_total,
                COALESCE(fin.total_session_extra_income, 0) as extra_income_total
            FROM `schedules` s
            LEFT JOIN `session_participants` sp ON s.id = sp.session_id
            LEFT JOIN (
                SELECT 
                    session_id,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_session_expense,
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_session_extra_income
                FROM `financial_records`
                WHERE session_id IS NOT NULL
                GROUP BY session_id
            ) fin ON s.id = fin.session_id
            GROUP BY s.id
            ORDER BY s.schedule_date DESC, s.id DESC
            LIMIT {$limit}
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['total_income'] = (int)$r['income_iuran'] + (int)$r['extra_income_total'];
            $r['net_profit'] = $r['total_income'] - (int)$r['expense_total'];
        }
        return $rows;
    }

    /**
     * Find single financial record
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `financial_records` WHERE `id` = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Update existing financial record
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `financial_records`
            SET `session_id` = ?, `type` = ?, `category` = ?, `amount` = ?, `title` = ?, `notes` = ?, `record_date` = ?
            WHERE `id` = ?
        ");
        return $stmt->execute([
            !empty($data['session_id']) ? (int)$data['session_id'] : null,
            ($data['type'] ?? 'expense') === 'income' ? 'income' : 'expense',
            $data['category'] ?? 'Lain-lain',
            (int)($data['amount'] ?? 0),
            $data['title'] ?? 'Transaksi',
            $data['notes'] ?? null,
            !empty($data['record_date']) ? $data['record_date'] : date('Y-m-d'),
            $id
        ]);
    }

    /**
     * Monthly income vs expense trend for the last N months (oldest first)
     */
    public static function getMonthlyTrend(int $months = 6): array {
        $pdo = Database::getConnection();
        $months = max(1, min(12, $months));
        $startMonth = date('Y-m-01', strtotime('-' . ($months - 1) . ' months', strtotime(date('Y-m-01'))));

        $trend = [];
        for ($i = 0; $i < $months; $i++) {
            $key = date('Y-m', strtotime("+{$i} months", strtotime($startMonth)));
            $trend[$key] = ['month' => $key, 'income' => 0, 'expense' => 0];
        }

        $iuran = $pdo->prepare("
            SELECT DATE_FORMAT(s.schedule_date, '%Y-%m') AS ym, COALESCE(SUM(sp.payment_amount), 0) AS total
            FROM `session_participants` sp
            JOIN `schedules` s ON sp.session_id = s.id
            WHERE sp.payment_status = 'paid' AND s.schedule_date >= ?
            GROUP BY ym
        ");
        $iuran->execute([$startMonth]);
        foreach ($iuran->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($trend[$row['ym']])) $trend[$row['ym']]['income'] += (int)$row['total'];
        }

        $fin = $pdo->prepare("
            SELECT DATE_FORMAT(record_date, '%Y-%m') AS ym,
                   COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS inc,
                   COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS exp
            FROM `financial_records`
            WHERE record_date >= ?
            GROUP BY ym
        ");
        $fin->execute([$startMonth]);
        foreach ($fin->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($trend[$row['ym']])) {
                $trend[$row['ym']]['income'] += (int)$row['inc'];
                $trend[$row['ym']]['expense'] += (int)$row['exp'];
            }
        }

        return array_values($trend);
    }

    /**
     * Expense (or income) totals grouped by category
     */
    public static function getCategoryBreakdown(int $sessionId = 0, string $month = '', string $type = 'expense'): array {
        $pdo = Database::getConnection();
        $where = ["type = ?"];
        $params = [$type === 'income' ? 'income' : 'expense'];

        if ($sessionId > 0) {
            $where[] = "session_id = ?";
            $params[] = $sessionId;
        }
        if (!empty($month)) {
            $where[] = "DATE_FORMAT(record_date, '%Y-%m') = ?";
            $params[] = $month;
        }

        $stmt = $pdo->prepare("
            SELECT category, COUNT(*) AS trx_count, COALESCE(SUM(amount), 0) AS total
            FROM `financial_records`
            WHERE " . implode(' AND ', $where) . "
            GROUP BY category
            ORDER BY total DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Participants whose fee is still unpaid (receivables)
     */
    public static function getUnpaidParticipants(int $sessionId = 0, string $month = '', int $limit = 200): array {
        $pdo = Database::getConnection();
        $where = ["sp.payment_status = 'unpaid'"];
        $params = [];

        if ($sessionId > 0) {
            $where[] = "sp.session_id = ?";
            $params[] = $sessionId;
        }
        if (!empty($month)) {
            $where[] = "DATE_FORMAT(s.schedule_date, '%Y-%m') = ?";
            $params[] = $month;
        }

        $limit = max(1, min(500, $limit));
        $stmt = $pdo->prepare("
            SELECT sp.session_id, sp.player_id, sp.payment_amount,
                   p.name AS player_name, p.phone AS player_phone,
                   s.venue_name, s.day_name, s.schedule_date
            FROM `session_participants` sp
            JOIN `players` p ON sp.player_id = p.id
            JOIN `schedules` s ON sp.session_id = s.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY s.schedule_date DESC, s.id DESC, p.name ASC
            LIMIT {$limit}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark a participant fee as paid (idempotent, never toggles back)
     */
    public static function markParticipantPaid(int $sessionId, int $playerId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `session_participants`
            SET `payment_status` = 'paid', `paid_at` = NOW()
            WHERE `session_id` = ? AND `player_id` = ? AND `payment_status` = 'unpaid'
        ");
        return $stmt->execute([$sessionId, $playerId]);
    }
}
