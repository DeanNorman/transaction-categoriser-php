<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->add('GET', '/transactions', fn () => Response::ok('list'));
        $this->router->add('GET', '/transactions/{id}', fn (Request $r, array $p) => Response::ok($p));
    }

    public function testMatchesAStaticPath(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/transactions'));
        self::assertSame(200, $response->status);
        self::assertSame(['data' => 'list'], $response->body);
    }

    public function testExtractsNamedSegments(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/transactions/abc-123'));
        self::assertSame(['data' => ['id' => 'abc-123']], $response->body);
    }

    public function testUnknownPathIs404(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/nothing'));
        self::assertSame(404, $response->status);
        self::assertSame('not_found', $response->body['error']['code']);
    }

    public function testKnownPathWrongMethodIs405(): void
    {
        $response = $this->router->dispatch(new Request('DELETE', '/transactions'));
        self::assertSame(405, $response->status);
        self::assertSame('method_not_allowed', $response->body['error']['code']);
    }

    public function testSegmentsDoNotSpanSlashes(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/transactions/a/b'));
        self::assertSame(404, $response->status);
    }
}
