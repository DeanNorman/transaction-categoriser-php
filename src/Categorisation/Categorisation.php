<?php
declare(strict_types=1);

namespace App\Categorisation;

use App\Transactions\Category;

/**
 * The outcome of categorising one description: the category, where it came
 * from (the id of the rule that matched, or 'fallback'), and the text that
 * matched so a reader can see why.
 */
final class Categorisation
{
    public const FALLBACK = 'fallback';

    public function __construct(
        public readonly Category $category,
        public readonly string $source,
        public readonly ?string $matched,
    ) {
    }

    public static function fallback(): self
    {
        return new self(Category::Miscellaneous, self::FALLBACK, null);
    }

    public function isFallback(): bool
    {
        return $this->source === self::FALLBACK;
    }
}
