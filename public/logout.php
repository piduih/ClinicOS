<?php
declare(strict_types=1);

use ClinicOS\Auth\Auth;
use ClinicOS\Security\Csrf;

$dbConfig = require dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verify($_POST['_csrf'] ?? null)) {
    http_response_code(405);
    exit('Invalid request.');
}

$user = Auth::user($dbConfig);\nAuditLogger::record(Connection::make($dbConfig), $user['id'] ?? null, 'auth.logout');\nAuth::logout();
header('Location: login.php');
exit;
