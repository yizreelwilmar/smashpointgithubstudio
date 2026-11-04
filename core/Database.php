<?php
// core/Database.php
// Powershoot Badminton Singleton PDO Wrapper

date_default_timezone_set('Asia/Jakarta');

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                @self::$instance->exec("SET time_zone = '+07:00'");
            } catch (PDOException $e) {
                if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                    require_once __DIR__ . '/../config/setup.php';
                    self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                    @self::$instance->exec("SET time_zone = '+07:00'");
                } else {
                    throw new RuntimeException("Database error: " . $e->getMessage());
                }
            }
        }
        return self::$instance;
    }
}
