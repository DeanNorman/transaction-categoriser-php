<?php
declare(strict_types=1);

namespace App\Db;

use PDO;

/**
 * A lazily opened PDO handle to MariaDB. Nothing connects until the first
 * handler asks for pdo(), so a request that never touches the database never
 * opens one, and a database outage surfaces as a handler's 503, not a crash.
 *
 * Configuration comes from the environment so the same image serves the
 * application database and, under test, the second database.
 */
final class Connection
{
    private ?PDO $pdo = null;

    /** @param callable(): PDO $open */
    public function __construct(private $open)
    {
    }

    public static function fromEnv(): self
    {
        return self::to(
            host: getenv('DB_HOST') ?: 'db',
            name: getenv('DB_NAME') ?: 'transactions',
            user: getenv('DB_USER') ?: 'app',
            password: getenv('DB_PASSWORD') ?: 'app',
        );
    }

    public static function to(string $host, string $name, string $user, string $password): self
    {
        return new self(static function () use ($host, $name, $user, $password): PDO {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);

            return new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        });
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= ($this->open)();
    }
}
