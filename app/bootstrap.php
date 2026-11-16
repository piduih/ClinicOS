<?php
declare(strict_types=1);

use ClinicOS\Database\Connection;

define('CLINICOS_ROOT', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'ClinicOS\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = CLINICOS_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$configFile = CLINICOS_ROOT . '/config/database.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Database configuration is missing. Copy config/database.example.php to config/database.php.');
}
$dbConfig = require $configFile;

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('CLINICOS_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

return $dbConfig;
