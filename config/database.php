<?php
// config/database.php
// Powershoot Badminton Management System Database Configuration

return [
    'driver'    => 'mysql',
    'host'      => getenv('SMASHPOINT_DB_HOST') ?: '127.0.0.1',
    'port'      => getenv('SMASHPOINT_DB_PORT') ?: 3306,
    'database'  => getenv('SMASHPOINT_DB_NAME') ?: 'powershoot_db',
    'username'  => getenv('SMASHPOINT_DB_USER') ?: 'root',
    'password'  => getenv('SMASHPOINT_DB_PASS') !== false ? getenv('SMASHPOINT_DB_PASS') : '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
