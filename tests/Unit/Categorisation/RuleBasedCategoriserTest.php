<?php
declare(strict_types=1);

namespace Tests\Unit\Categorisation;

use App\Categorisation\Categorisation;
use App\Categorisation\RuleBasedCategoriser;
use App\Transactions\Category;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RuleBasedCategoriserTest extends TestCase
{
    private RuleBasedCategoriser $categoriser;

    protected function setUp(): void
    {
        $this->categoriser = RuleBasedCategoriser::withDefaultRules();
    }

    /**
     * Every distinct description in the supplied history, with the category
     * and rule it must land on. This is the contract the rule table was
     * derived to satisfy (docs/prompts/01-derive-rules.md).
     *
     * @return array<string, array{Category, string}>
     */
    public static function expectedForHistory(): array
    {
        return [
            'Albert Heijn Purchase'   => [Category::Groceries, 'groceries.albert-heijn'],
            'AH Online Groceries'     => [Category::Groceries, 'groceries.ah'],
            'Eneco Energy Bill'       => [Category::Utilities, 'utilities.energy'],
            'Payment to Ziggo'        => [Category::Utilities, 'utilities.telecom'],
            'T-Mobile Bill Payment'   => [Category::Utilities, 'utilities.telecom'],
            'NS Train Ticket'         => [Category::Transportation, 'transport.rail'],
            'Car Lease Payment'       => [Category::Transportation, 'transport.car'],
            'Netflix Subscription'    => [Category::Entertainment, 'entertainment.streaming'],
            'Bol.com Purchase'        => [Category::Shopping, 'shopping.online'],
            'Rent Payment'            => [Category::Housing, 'housing.rent'],
            'Municipal Tax Payment'   => [Category::Housing, 'housing.taxes'],
            'Salary from Employer'    => [Category::Miscellaneous, Categorisation::FALLBACK],
            'Tax Refund'              => [Category::Miscellaneous, Categorisation::FALLBACK],
            'Transfer to ING Account' => [Category::Miscellaneous, Categorisation::FALLBACK],
            'Payment to Rabobank'     => [Category::Miscellaneous, Categorisation::FALLBACK],
            'Bunq Transfer'           => [Category::Miscellaneous, Categorisation::FALLBACK],
            'PayPal Transfer'         => [Category::Miscellaneous, Categorisation::FALLBACK],
            'iDEAL Payment'           => [Category::Miscellaneous, Categorisation::FALLBACK],
            'Payment to Credit Card'  => [Category::Miscellaneous, Categorisation::FALLBACK],
            'Geldmaat ATM Withdrawal' => [Category::Miscellaneous, Categorisation::FALLBACK],
        ];
    }

    /** @return iterable<string, array{string, Category, string}> */
    public static function historyDescriptions(): iterable
    {
        foreach (self::expectedForHistory() as $description => [$category, $source]) {
            yield $description => [$description, $category, $source];
        }
    }

    #[DataProvider('historyDescriptions')]
    public function testEachDescriptionInTheHistoryLandsWhereExpected(string $description, Category $category, string $source): void
    {
        $result = $this->categoriser->categorise($description);
        self::assertSame($category, $result->category, $description);
        self::assertSame($source, $result->source, $description);
    }

    public function testTheExpectationTableCoversTheWholeFixture(): void
    {
        $handle = fopen(__DIR__ . '/../../fixtures/transaction_history.csv', 'r');
        self::assertNotFalse($handle);
        fgetcsv($handle, escape: '');
        $seen = [];
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $seen[$row[3]] = true;
        }
        fclose($handle);

        self::assertEqualsCanonicalizing(array_keys(self::expectedForHistory()), array_keys($seen));
    }

    public function testEveryRowOfTheFixtureCategorisesWithoutError(): void
    {
        $handle = fopen(__DIR__ . '/../../fixtures/transaction_history.csv', 'r');
        self::assertNotFalse($handle);
        fgetcsv($handle, escape: '');
        $counts = [];
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            $category = $this->categoriser->categorise($row[3])->category->value;
            $counts[$category] = ($counts[$category] ?? 0) + 1;
        }
        fclose($handle);

        self::assertSame(1000, array_sum($counts));
        self::assertSame(95, $counts['Groceries']);         // 55 Albert Heijn + 40 AH Online
        self::assertSame(146, $counts['Utilities']);        // 49 Eneco + 54 Ziggo + 43 T-Mobile
        self::assertSame(114, $counts['Transportation']);   // 54 NS + 60 Car Lease
        self::assertSame(101, $counts['Housing']);          // 42 Rent + 59 Municipal Tax
        self::assertSame(444, $counts['Miscellaneous']);
    }

    /** @return iterable<string, array{string, Category}> */
    public static function realWorldVariants(): iterable
    {
        yield 'upper case with store number' => ['ALBERT HEIJN 1234 AMSTERDAM', Category::Groceries];
        yield 'extra whitespace' => ["  netflix.com   subscription ", Category::Entertainment];
        yield 'Uber Eats is food' => ['Uber Eats order 4471', Category::DiningOut];
        yield 'Uber alone is a ride' => ['Uber trip to Schiphol', Category::Transportation];
        yield 'word boundary: rental is not rent' => ['Bike rental Amsterdam', Category::Miscellaneous];
        yield 'word boundary: transactions is not NS' => ['Fee for transactions', Category::Miscellaneous];
        yield 'word boundary: Barclays is not a bar' => ['Barclays fee', Category::Miscellaneous];
        yield 'health insurer' => ['Zilveren Kruis premie', Category::Healthcare];
        yield 'student finance' => ['DUO studiefinanciering', Category::Education];
        yield 'empty description' => ['', Category::Miscellaneous];
    }

    #[DataProvider('realWorldVariants')]
    public function testVariantsOutsideTheHistory(string $description, Category $category): void
    {
        self::assertSame($category, $this->categoriser->categorise($description)->category, $description);
    }

    public function testTheResultSaysWhatMatched(): void
    {
        $result = $this->categoriser->categorise('Payment to Ziggo');
        self::assertSame('Ziggo', $result->matched);
        self::assertFalse($result->isFallback());

        $fallback = $this->categoriser->categorise('Salary from Employer');
        self::assertNull($fallback->matched);
        self::assertTrue($fallback->isFallback());
    }
}
