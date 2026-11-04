<?php
// shared/core/Database.php
// Thread-safe Singleton PDO Database Wrapper (PHP 7.2+ Compatible)

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                    require_once __DIR__ . '/../config/setup.php';
                    self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
                } else {
                    throw new RuntimeException("Database connection error: " . $e->getMessage());
                }
            }
        }
        return self::$instance;
    }
}
