<?php
declare(strict_types=1);

namespace App\Categorisation;

use App\Transactions\Category;

/** One keyword pattern paired with a category. Immutable. */
final class Rule
{
    public function __construct(
        public readonly string $id,
        public readonly Category $category,
        public readonly string $pattern,   // a PCRE with word boundaries, case-insensitive
        public readonly string $note,      // why this rule exists, for the reader
    ) {
    }

    /** Returns the matched text, or null when the rule does not apply. */
    public function match(string $description): ?string
    {
        return preg_match($this->pattern, $description, $m) === 1 ? $m[0] : null;
    }
}
