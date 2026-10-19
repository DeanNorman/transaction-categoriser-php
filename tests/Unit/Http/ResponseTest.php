<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testSuccessHasDataAndOptionalMeta(): void
    {
        self::assertSame(['data' => [1]], Response::ok([1])->body);
        self::assertSame(['data' => [1], 'meta' => ['count' => 1]], Response::ok([1], meta: ['count' => 1])->body);
        self::assertSame(201, Response::ok(null, 201)->status);
    }

    public function testFailureHasErrorWithOptionalFields(): void
    {
        $plain = Response::error(404, 'not_found', 'gone');
        self::assertSame(['error' => ['code' => 'not_found', 'message' => 'gone']], $plain->body);

        $withFields = Response::error(422, 'validation_failed', 'bad', ['amount' => 'is required']);
        self::assertSame(['amount' => 'is required'], $withFields->body['error']['fields']);
    }
}
