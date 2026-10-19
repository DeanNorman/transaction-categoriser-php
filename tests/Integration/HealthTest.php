<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Db\Connection;
use App\Http\Kernel;
use App\Http\Request;

final class HealthTest extends IntegrationTestCase
{
    public function testHealthIsOkWhenTheDatabaseAnswers(): void
    {
        $response = $this->request('GET', '/health');

        self::assertSame(200, $response->status);
        self::assertSame(['status' => 'ok', 'database' => 'ok'], $response->body['data']);
    }

    public function testHealthIs503WhenTheDatabaseDoesNot(): void
    {
        $kernel = new Kernel(Connection::to('no-such-host', 'x', 'x', 'x'));
        $response = $kernel->handle(new Request('GET', '/health'));

        self::assertSame(503, $response->status);
        self::assertSame('database_unavailable', $response->body['error']['code']);
    }

    public function testADatabaseOutageDuringAWriteIsA500NotACrash(): void
    {
        $kernel = new Kernel(Connection::to('no-such-host', 'x', 'x', 'x'));
        $response = $kernel->handle(new Request('POST', '/transactions', [], self::sample()));

        self::assertSame(500, $response->status);
        self::assertSame('internal_error', $response->body['error']['code']);
    }
}
