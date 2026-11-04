<?php
// index.php
// SMASHPOINT SaaS Master Dispatcher & Subdomain Router

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$hostParts = explode('.', $host);
$subdomain = '';

if (count($hostParts) >= 3) {
    $subdomain = strtolower($hostParts[0]);
}

// Check explicit view query param or clean route page
$view = isset($_GET['view']) ? $_GET['view'] : '';
$page = isset($_GET['page']) ? trim($_GET['page'], '/') : '';

// 1. Route CMS / Admin Subdomain or Route
if ($subdomain === 'cms' || $subdomain === 'admin' || $view === 'cms' || $view === 'admin' || $page === 'admin' || $page === 'cms') {
    require __DIR__ . '/admin/index.php';
    exit;
}

// 2. Route Community App Subdomain (e.g. app.mabar.id)
if ($subdomain === 'app' || $view === 'app' || $page === 'app') {
    require __DIR__ . '/app/index.php';
    exit;
}

// 3. Route Public Gallery Page with Clean Hashed Routing
if ($subdomain === 'gallery' || $view === 'gallery' || $page === 'gallery' || $page === 'galeri' || strpos($page, 'gallery/') === 0 || strpos($page, 'galeri/') === 0) {
    if (strpos($page, '/') !== false) {
        $parts = explode('/', $page);
        if (!empty($parts[1]) && !isset($_GET['session'])) {
            $_GET['session'] = $parts[1];
        }
    }
    require __DIR__ . '/gallery.php';
    exit;
}

// 3. Default: Main Community Landing Page
require __DIR__ . '/views/landing/index.php';
