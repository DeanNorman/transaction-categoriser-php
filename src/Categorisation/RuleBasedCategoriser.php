<?php
declare(strict_types=1);

namespace App\Categorisation;

/**
 * Walks the rule table in order; the first match decides. No match is the
 * fallback: Miscellaneous, source 'fallback'. A pure function of the
 * description, which is what makes it trivial to test and safe to call
 * synchronously inside POST.
 */
final class RuleBasedCategoriser implements Categoriser
{
    public function __construct(private readonly RuleTable $table)
    {
    }

    public static function withDefaultRules(): self
    {
        return new self(RuleTable::fromFile());
    }

    public function categorise(string $description): Categorisation
    {
        $text = trim(preg_replace('/\s+/u', ' ', $description) ?? $description);

        foreach ($this->table->all() as $rule) {
            $matched = $rule->match($text);
            if ($matched !== null) {
                return new Categorisation($rule->category, $rule->id, $matched);
            }
        }

        return Categorisation::fallback();
    }
}
