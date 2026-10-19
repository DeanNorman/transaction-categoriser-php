<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Categorisation\Categorisation;
use App\Support\Clock;
use App\Transactions\Category;
use App\Transactions\DuplicateReferenceException;
use App\Transactions\TransactionInput;
use App\Transactions\TransactionRepository;
use App\Transactions\TransactionType;
use DateTimeImmutable;
use DateTimeZone;

/** The repository against the real schema: round trips, the unique key, ordering and the cap. */
final class TransactionRepositoryTest extends IntegrationTestCase
{
    private TransactionRepository $repository;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2026-10-19 12:00:00.123456', new DateTimeZone('UTC'));
        $this->repository = new TransactionRepository($this->connection, new Clock(fn () => $this->now));
    }

    private function input(?string $reference, string $description = 'Eneco Energy Bill'): TransactionInput
    {
        return new TransactionInput(
            reference: $reference,
            amount: '42.60',
            occurredAt: new DateTimeImmutable('2024-02-03 16:29:37.808433', new DateTimeZone('UTC')),
            description: $description,
            type: TransactionType::Debit,
            accountNumber: 'NLRABO5127825843',
        );
    }

    public function testInsertThenFindRoundTripsEveryField(): void
    {
        $stored = $this->repository->insert($this->input('TXN00013'), new Categorisation(Category::Utilities, 'utilities.energy', 'Eneco'));
        $found = $this->repository->find($stored->id);

        self::assertNotNull($found);
        self::assertSame($stored->toArray(), $found->toArray());
        self::assertSame('2026-10-19T12:00:00.123456Z', $found->toArray()['categorised_at'] ?? $found->toArray()['categorisation']['categorised_at']);
        self::assertSame('2024-02-03T16:29:37.808433Z', $found->toArray()['timestamp']);
    }

    public function testTheUniqueReferenceIsEnforcedByTheDatabase(): void
    {
        $this->repository->insert($this->input('TXN00013'), Categorisation::fallback());

        $this->expectException(DuplicateReferenceException::class);
        $this->repository->insert($this->input('TXN00013'), Categorisation::fallback());
    }

    public function testFindReturnsNullForAnUnknownId(): void
    {
        self::assertNull($this->repository->find('00000000-0000-4000-8000-000000000000'));
    }

    public function testListIsNewestFirstAndHonoursTheLimit(): void
    {
        foreach (['first', 'second', 'third'] as $i => $description) {
            $this->now = $this->now->modify("+$i seconds");
            $this->repository->insert($this->input(null, $description), Categorisation::fallback());
        }

        $all = $this->repository->list(null, 10);
        self::assertSame(['third', 'second', 'first'], array_map(fn ($t) => $t->description, $all));

        $capped = $this->repository->list(null, 2);
        self::assertSame(['third', 'second'], array_map(fn ($t) => $t->description, $capped));
    }

    public function testListFiltersByCategory(): void
    {
        $this->repository->insert($this->input('a', 'Rent Payment'), new Categorisation(Category::Housing, 'housing.rent', 'Rent'));
        $this->repository->insert($this->input('b', 'Netflix Subscription'), new Categorisation(Category::Entertainment, 'entertainment.streaming', 'Netflix'));

        $housing = $this->repository->list(Category::Housing, 10);
        self::assertCount(1, $housing);
        self::assertSame('Rent Payment', $housing[0]->description);
        self::assertSame([], $this->repository->list(Category::Education, 10));
    }
}
