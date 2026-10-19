<?php
declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    public function testMintsVersion4Uuids(): void
    {
        for ($i = 0; $i < 100; $i++) {
            self::assertTrue(Uuid::isValid(Uuid::v4()));
        }
    }

    public function testMintsDistinctIds(): void
    {
        $ids = array_map(fn () => Uuid::v4(), range(1, 1000));
        self::assertCount(1000, array_unique($ids));
    }

    public function testRejectsOtherShapes(): void
    {
        self::assertFalse(Uuid::isValid('TXN00001'));
        self::assertFalse(Uuid::isValid('123e4567-e89b-12d3-a456-426614174000')); // version 1
    }
}
