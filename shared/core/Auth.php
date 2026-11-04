<?php
// shared/core/Auth.php
// Admin Session Authentication Guard (Multi-tenant SaaS)

require_once __DIR__ . '/Database.php';

class Auth {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool {
        self::init();
        return isset($_SESSION['smashpoint_user_id']);
    }

    public static function user() {
        self::init();
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['smashpoint_user_id'],
            'club_id' => $_SESSION['smashpoint_club_id'],
            'username' => $_SESSION['smashpoint_username'],
            'name' => $_SESSION['smashpoint_name'],
            'role' => $_SESSION['smashpoint_role']
        ];
    }

    public static function attempt(string $username, string $password): bool {
        self::init();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1");
        $stmt->execute([trim($username), trim($username)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['smashpoint_user_id'] = $user['id'];
            $_SESSION['smashpoint_club_id'] = $user['club_id'];
            $_SESSION['smashpoint_username'] = $user['username'];
            $_SESSION['smashpoint_name'] = $user['name'];
            $_SESSION['smashpoint_role'] = $user['role'];
            return true;
        }

        return false;
    }

    public static function logout() {
        self::init();
        unset($_SESSION['smashpoint_user_id']);
        unset($_SESSION['smashpoint_club_id']);
        unset($_SESSION['smashpoint_username']);
        unset($_SESSION['smashpoint_name']);
        unset($_SESSION['smashpoint_role']);
    }

    public static function requireAdmin() {
        if (!self::check()) {
            $base = self::baseUrl();
            header("Location: {$base}?page=login");
            exit;
        }
    }

    public static function baseUrl(): string {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = rtrim(str_replace('\\', '/', $scriptDir), '/');
        return $scriptDir . '/index.php';
    }
}
