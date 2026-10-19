<?php
declare(strict_types=1);

namespace App\Categorisation;

/**
 * Anything that can place a description in a category.
 *
 * The shipped implementation is RuleBasedCategoriser: deterministic, offline,
 * free. An LLM-backed implementation would sit behind this same interface and
 * be consulted only when the rules fall back; it is deliberately not shipped
 * (see docs/adr/0001-rules-at-runtime.md).
 */
interface Categoriser
{
    public function categorise(string $description): Categorisation;
}
