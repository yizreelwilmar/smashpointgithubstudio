<?php
// shared/core/Tenant.php
// Multi-Tenant Context & Subdomain Resolver

require_once __DIR__ . '/Database.php';

class Tenant {
    private static $current = null;

    public static function resolve() {
        if (self::$current !== null) {
            return self::$current;
        }

        $pdo = Database::getConnection();
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $slug = null;

        // 1. Check Subdomain (e.g. powershoot.mabar.id or powershoot.localhost)
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            $potentialSubdomain = strtolower($parts[0]);
            if (!in_array($potentialSubdomain, ['www', 'app', 'cms', 'admin', 'api'])) {
                $slug = $potentialSubdomain;
            }
        }

        // 2. Check query parameter or cookie for local testing
        if (!$slug && isset($_GET['club'])) {
            $slug = trim($_GET['club']);
        }

        if ($slug) {
            $stmt = $pdo->prepare("SELECT * FROM `clubs` WHERE `slug` = ? OR `subdomain` = ? LIMIT 1");
            $stmt->execute([$slug, $slug]);
            $found = $stmt->fetch();
            if ($found) {
                self::$current = $found;
                return self::$current;
            }
        }

        // 3. Fallback to default club (Powershoot Badminton)
        $default = $pdo->query("SELECT * FROM `clubs` WHERE `is_active` = 1 ORDER BY id ASC LIMIT 1")->fetch();
        self::$current = $default ?: [
            'id' => 1,
            'name' => 'Powershoot Badminton',
            'slug' => 'powershoot',
            'subdomain' => 'powershoot',
            'sport_type' => 'Badminton',
            'city' => 'Jakarta Selatan',
            'tagline' => 'Komunitas Mabar Badminton & Arena Rotasi Cerdas',
            'primary_color' => '#10e599',
            'plan' => 'pro'
        ];

        return self::$current;
    }

    public static function id() {
        $curr = self::resolve();
        return (int)$curr['id'];
    }

    public static function name() {
        $curr = self::resolve();
        return $curr['name'];
    }

    public static function all() {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM `clubs` WHERE `is_active` = 1 ORDER BY name ASC")->fetchAll();
    }
}
