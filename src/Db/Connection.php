<?php
declare(strict_types=1);

namespace App\Db;

use PDO;

/**
 * One place that knows how to open a PDO handle to MariaDB.
 * Configuration comes from the environment so the same image serves the
 * application database and, under test, the second database.
 */
final class Connection
{
    public static function fromEnv(): PDO
    {
        return self::open(
            host: getenv('DB_HOST') ?: 'db',
            name: getenv('DB_NAME') ?: 'transactions',
            user: getenv('DB_USER') ?: 'app',
            password: getenv('DB_PASSWORD') ?: 'app',
        );
    }

    public static function open(string $host, string $name, string $user, string $password): PDO
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
