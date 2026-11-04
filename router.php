<?php
// router.php — PHP Built-in Server Router for Powershoot Badminton System
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. Serve static files directly if file exists
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// 2. App operational route (/app/)
if ($uri === '/app' || strpos($uri, '/app/') === 0) {
    if (file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
        return false;
    }
    chdir(__DIR__ . '/app');
    require __DIR__ . '/app/index.php';
    return true;
}

// 3. Admin / CMS route (/admin/ or /cms/)
if ($uri === '/admin' || strpos($uri, '/admin/') === 0 || $uri === '/cms' || strpos($uri, '/cms/') === 0) {
    if (file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
        return false;
    }
    chdir(__DIR__ . '/admin');
    require __DIR__ . '/admin/index.php';
    return true;
}

// 4. API route (/api/)
if ($uri === '/api' || strpos($uri, '/api/') === 0) {
    chdir(__DIR__ . '/api');
    require __DIR__ . '/api/index.php';
    return true;
}

// 5. Gallery route
if ($uri === '/gallery' || $uri === '/galeri') {
    require __DIR__ . '/gallery.php';
    return true;
}

// 6. Root Landing Page
chdir(__DIR__);
require __DIR__ . '/index.php';
return true;
