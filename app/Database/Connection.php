<?php
declare(strict_types=1);

namespace ClinicOS\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    public static function make(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        if ($database === '') {
            throw new RuntimeException('Database name is required.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        try {
            return new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to the database.', 0, $exception);
        }
    }
}
