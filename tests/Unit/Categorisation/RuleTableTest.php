<?php
declare(strict_types=1);

namespace Tests\Unit\Categorisation;

use App\Categorisation\Rule;
use App\Categorisation\RuleTable;
use App\Transactions\Category;
use PHPUnit\Framework\TestCase;

/** The rule table is data; these tests keep it well formed as it grows. */
final class RuleTableTest extends TestCase
{
    public function testEveryRuleIdIsUniqueAndEveryPatternCompiles(): void
    {
        $rules = RuleTable::fromFile()->all();
        self::assertNotEmpty($rules);
        self::assertCount(count($rules), array_unique(array_map(fn (Rule $r) => $r->id, $rules)));
    }

    public function testNoRuleMapsToMiscellaneous(): void
    {
        foreach (RuleTable::fromFile()->all() as $rule) {
            self::assertNotSame(Category::Miscellaneous, $rule->category, "{$rule->id} maps to the fallback category");
        }
    }

    public function testEveryCategoryExceptMiscellaneousHasAtLeastOneRule(): void
    {
        $covered = array_map(fn (Rule $r) => $r->category, RuleTable::fromFile()->all());
        foreach (Category::cases() as $category) {
            if ($category === Category::Miscellaneous) {
                continue;
            }
            self::assertContains($category, $covered, "{$category->value} has no rule");
        }
    }

    public function testRuleIdsAreNamespacedByCategory(): void
    {
        foreach (RuleTable::fromFile()->all() as $rule) {
            self::assertMatchesRegularExpression('/^[a-z]+\.[a-z-]+$/', $rule->id);
        }
    }

    public function testDuplicateIdsAreRejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rules');
        file_put_contents($path, '<?php use App\Categorisation\Rule; use App\Transactions\Category; return [new Rule("a.b", Category::Groceries, "/x/", ""), new Rule("a.b", Category::Housing, "/y/", "")];');
        $this->expectException(\LogicException::class);
        try {
            RuleTable::fromFile($path);
        } finally {
            unlink($path);
        }
    }
}
