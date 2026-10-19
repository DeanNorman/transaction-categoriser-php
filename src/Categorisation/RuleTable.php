<?php
declare(strict_types=1);

namespace App\Categorisation;

/** Loads rules.php and checks it is well formed before anything uses it. */
final class RuleTable
{
    /** @var list<Rule> */
    private array $rules;

    /** @param list<Rule> $rules */
    private function __construct(array $rules)
    {
        $ids = [];
        foreach ($rules as $rule) {
            if (isset($ids[$rule->id])) {
                throw new \LogicException("Duplicate rule id: {$rule->id}");
            }
            if (@preg_match($rule->pattern, '') === false) {
                throw new \LogicException("Rule {$rule->id} has an invalid pattern");
            }
            $ids[$rule->id] = true;
        }
        $this->rules = $rules;
    }

    public static function fromFile(string $path = __DIR__ . '/rules.php'): self
    {
        /** @var list<Rule> $rules */
        $rules = require $path;

        return new self($rules);
    }

    /** @return list<Rule> */
    public function all(): array
    {
        return $this->rules;
    }
}
