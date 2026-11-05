<?php
// core/Auth.php
// Admin Session Authentication Guard (PHP 7.2+ Compatible)

require_once __DIR__ . '/Database.php';

class Auth {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool {
        self::init();
        return isset($_SESSION['smashpoint_admin_id']);
    }

    public static function user() {
        self::init();
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['smashpoint_admin_id'],
            'username' => $_SESSION['smashpoint_admin_user'],
            'name' => $_SESSION['smashpoint_admin_name'],
            'role' => $_SESSION['smashpoint_admin_role'],
            'assigned_session_id' => isset($_SESSION['smashpoint_assigned_session_id']) ? (int)$_SESSION['smashpoint_assigned_session_id'] : null
        ];
    }

    public static function isPic(): bool {
        $u = self::user();
        return $u && $u['role'] === 'pic';
    }

    public static function id(): ?int {
        self::init();
        return isset($_SESSION['smashpoint_admin_id']) ? (int)$_SESSION['smashpoint_admin_id'] : null;
    }

    public static function isPicFoto(): bool {
        $u = self::user();
        return $u && $u['role'] === 'pic_foto';
    }

    public static function isAdmin(): bool {
        $u = self::user();
        return $u && $u['role'] === 'admin';
    }

    public static function assignedSessionId(): ?int {
        $u = self::user();
        return ($u && !empty($u['assigned_session_id'])) ? (int)$u['assigned_session_id'] : null;
    }

    public static function attempt(string $username, string $password): bool {
        self::init();
        $pdo = Database::getConnection();
        // Allow login using username OR phone number
        $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE LOWER(`username`) = LOWER(?) OR `phone` = ? LIMIT 1");
        $stmt->execute([trim($username), trim($username)]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['smashpoint_admin_id'] = (int)$admin['id'];
            $_SESSION['smashpoint_admin_user'] = $admin['username'];
            $_SESSION['smashpoint_admin_name'] = $admin['name'];
            $_SESSION['smashpoint_admin_role'] = $admin['role'];
            $_SESSION['smashpoint_assigned_session_id'] = $admin['assigned_session_id'] ? (int)$admin['assigned_session_id'] : null;
            return true;
        }

        return false;
    }

    public static function updatePassword(int $adminId, string $currentPassword, string $newPassword): bool {
        self::init();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT password_hash FROM `admins` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($currentPassword, $admin['password_hash'])) {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtUpdate = $pdo->prepare("UPDATE `admins` SET `password_hash` = ? WHERE `id` = ?");
            return $stmtUpdate->execute([$newHash, $adminId]);
        }
        return false;
    }

    public static function logout() {

        self::init();
        unset($_SESSION['smashpoint_admin_id']);
        unset($_SESSION['smashpoint_admin_user']);
        unset($_SESSION['smashpoint_admin_name']);
        unset($_SESSION['smashpoint_admin_role']);
        unset($_SESSION['smashpoint_assigned_session_id']);
    }

    public static function requireAdmin() {
        if (!self::check()) {
            $base = self::baseUrl();
            header("Location: {$base}?page=admin-login");
            exit;
        }
    }

    public static function baseUrl(): string {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
        return $scriptDir . '/index.php';
    }

    public static function appUrl(string $path = ''): string {
        $base = dirname($_SERVER['SCRIPT_NAME']);
        $base = rtrim(str_replace('\\', '/', $base), '/');
        if (empty($path) || $path === '/') return $base ? $base : '/';
        return ($base ? $base : '') . '/' . ltrim($path, '/');
    }
}
