<?php
declare(strict_types=1);

use ClinicOS\Database\Connection;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$configFile = dirname(__DIR__) . '/config/database.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Missing config/database.php. Copy the example config first.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';
$dbConfig = require $configFile;

$email = strtolower(trim((string) readline('Admin email: ')));
$name = trim((string) readline('Admin full name: ')));
$password = (string) readline('Admin password (minimum 12 characters): ');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || strlen($password) < 12) {
    fwrite(STDERR, "Invalid input. Check email, name and password length.\n");
    exit(1);
}

$pdo = Connection::make($dbConfig);
$statement = $pdo->prepare(
    "INSERT INTO users (full_name, email, password_hash, role, is_active)
     VALUES (:name, :email, :password_hash, 'admin', 1)"
);

try {
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Administrator account created. Delete or retain this CLI script according to your deployment policy.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "Could not create administrator. The email may already exist.\n");
    exit(1);
}
