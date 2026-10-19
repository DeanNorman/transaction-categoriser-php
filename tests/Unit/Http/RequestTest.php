<?php
declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testEmptyBodyIsNullAndNotMalformed(): void
    {
        self::assertSame([null, false], Request::decode(''));
        self::assertSame([null, false], Request::decode("  \n"));
    }

    public function testObjectBodyIsDecoded(): void
    {
        self::assertSame([['amount' => 1], false], Request::decode('{"amount": 1}'));
    }

    public function testBrokenJsonIsMalformed(): void
    {
        self::assertSame([null, true], Request::decode('{"amount": '));
    }

    public function testScalarJsonIsMalformed(): void
    {
        self::assertSame([null, true], Request::decode('42'));
    }
}
