<?php
declare(strict_types=1);

namespace Tests\Integration;

/** GET /transactions/{id} and GET /transactions, end to end against the test database. */
final class RetrieveTransactionTest extends IntegrationTestCase
{
    public function testAStoredTransactionIsReadBackExactlyAsItWasReturned(): void
    {
        $posted = $this->request('POST', '/transactions', self::sample())->body['data'];
        $fetched = $this->request('GET', '/transactions/' . $posted['id']);

        self::assertSame(200, $fetched->status);
        self::assertSame($posted, $fetched->body['data']);
    }

    public function testUnknownAndMalformedIdsAre404(): void
    {
        self::assertSame(404, $this->request('GET', '/transactions/00000000-0000-4000-8000-000000000000')->status);
        self::assertSame(404, $this->request('GET', '/transactions/TXN00001')->status);
        self::assertSame('transaction_not_found', $this->request('GET', '/transactions/x')->body['error']['code']);
    }

    public function testTheListIsNewestFirstWithMeta(): void
    {
        foreach (['Albert Heijn Purchase', 'Netflix Subscription', 'Rent Payment'] as $i => $description) {
            $this->request('POST', '/transactions', self::sample(['reference' => "R$i", 'description' => $description]));
        }

        $response = $this->request('GET', '/transactions');

        self::assertSame(200, $response->status);
        self::assertSame(['Rent Payment', 'Netflix Subscription', 'Albert Heijn Purchase'], array_column($response->body['data'], 'description'));
        self::assertSame(['count' => 3, 'cap' => 500, 'category' => null], $response->body['meta']);
    }

    public function testTheListCanBeFilteredByCategory(): void
    {
        foreach (['Albert Heijn Purchase', 'Netflix Subscription', 'AH Online Groceries'] as $i => $description) {
            $this->request('POST', '/transactions', self::sample(['reference' => "R$i", 'description' => $description]));
        }

        $response = $this->request('GET', '/transactions?category=Groceries');

        self::assertSame(['AH Online Groceries', 'Albert Heijn Purchase'], array_column($response->body['data'], 'description'));
        self::assertSame('Groceries', $response->body['meta']['category']);
        self::assertSame(2, $response->body['meta']['count']);
    }

    public function testAnUnknownCategoryIs422(): void
    {
        $response = $this->request('GET', '/transactions?category=Pets');

        self::assertSame(422, $response->status);
        self::assertStringContainsString('Miscellaneous', $response->body['error']['fields']['category']);
    }

    public function testAnEmptyTableListsNothing(): void
    {
        $response = $this->request('GET', '/transactions');

        self::assertSame([], $response->body['data']);
        self::assertSame(0, $response->body['meta']['count']);
    }
}
