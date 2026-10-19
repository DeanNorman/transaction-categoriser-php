<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Db\Connection;
use App\Http\Kernel;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Runs the real Kernel against the second database (TEST_DB_NAME) on the
 * same MariaDB the stack uses. Every test starts from an empty table.
 * Requests go through Kernel::handle, the same call the front controller
 * makes; only nginx and PHP-FPM are outside the loop (scripts/smoke.sh
 * covers those).
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Connection $connection;
    protected Kernel $kernel;

    protected function setUp(): void
    {
        $this->connection = self::connectionForTests();
        $this->connection->pdo()->exec('DELETE FROM transactions');
        $this->kernel = new Kernel($this->connection);
    }

    public static function connectionForTests(): Connection
    {
        $name = getenv('TEST_DB_NAME') ?: '';
        if ($name === '' || $name === (getenv('DB_NAME') ?: 'transactions')) {
            self::fail('TEST_DB_NAME must name a database other than DB_NAME; refusing to run against application data');
        }

        return Connection::to(
            host: getenv('DB_HOST') ?: 'db',
            name: $name,
            user: getenv('DB_USER') ?: 'app',
            password: getenv('DB_PASSWORD') ?: 'app',
        );
    }

    /** @param array<string,mixed>|string|null $body an array is JSON-encoded; a string is sent raw */
    protected function request(string $method, string $path, array|string|null $body = null): Response
    {
        $query = [];
        $parts = parse_url($path);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        $raw = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : ($body ?? '');
        [$decoded, $malformed] = Request::decode($raw);

        return $this->kernel->handle(new Request($method, $parts['path'] ?? '/', $query, $decoded, $malformed));
    }

    /** @return array<string,mixed> */
    protected static function sample(array $overrides = []): array
    {
        return $overrides + [
            'reference' => 'TXN00001',
            'amount' => -87.18,
            'timestamp' => '2024-05-16 07:22:18.808433',
            'description' => 'Municipal Tax Payment',
            'type' => 'debit',
            'account_number' => 'NLINGB1944573686',
        ];
    }

    protected function rowCount(): int
    {
        return (int) $this->connection->pdo()->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    }
}
