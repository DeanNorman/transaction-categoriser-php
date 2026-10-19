<?php
declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

/** The one place "now" comes from, so tests can hold time still. */
final class Clock
{
    /** @param (callable(): DateTimeImmutable)|null $now */
    public function __construct(private $now = null)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now !== null
            ? ($this->now)()
            : new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
