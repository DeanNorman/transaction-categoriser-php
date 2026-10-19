<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Support\Uuid;

/** POST /transactions, end to end against the test database. */
final class SubmitTransactionTest extends IntegrationTestCase
{
    public function testAValidSubmissionIsStoredCategorisedAndReturned(): void
    {
        $response = $this->request('POST', '/transactions', self::sample());

        self::assertSame(201, $response->status);
        $data = $response->body['data'];
        self::assertTrue(Uuid::isValid($data['id']));
        self::assertSame('TXN00001', $data['reference']);
        self::assertSame('-87.18', $data['amount']);
        self::assertSame('2024-05-16T07:22:18.808433Z', $data['timestamp']);
        self::assertSame('Municipal Tax Payment', $data['description']);
        self::assertSame('debit', $data['type']);
        self::assertSame('NLINGB1944573686', $data['account_number']);
        self::assertSame('Housing', $data['category']);
        self::assertSame('housing.taxes', $data['categorisation']['source']);
        self::assertSame(1, $this->rowCount());

        $row = $this->connection->pdo()->query('SELECT * FROM transactions')->fetch();
        self::assertSame($data['id'], $row['id']);
        self::assertSame('-87.18', $row['amount']);
        self::assertSame('2024-05-16 07:22:18.808433', $row['occurred_at']);
        self::assertSame('Housing', $row['category']);
        self::assertSame('housing.taxes', $row['categorisation_source']);
    }

    public function testAnUnknownDescriptionFallsBackToMiscellaneous(): void
    {
        $response = $this->request('POST', '/transactions', self::sample(['description' => 'Salary from Employer']));

        self::assertSame(201, $response->status);
        self::assertSame('Miscellaneous', $response->body['data']['category']);
        self::assertSame('fallback', $response->body['data']['categorisation']['source']);
    }

    public function testARepeatedReferenceIs409AndStoresNothing(): void
    {
        self::assertSame(201, $this->request('POST', '/transactions', self::sample())->status);
        $second = $this->request('POST', '/transactions', self::sample(['amount' => 1]));

        self::assertSame(409, $second->status);
        self::assertSame('duplicate_reference', $second->body['error']['code']);
        self::assertSame(['reference' => 'already exists'], $second->body['error']['fields']);
        self::assertSame(1, $this->rowCount());
    }

    public function testSubmissionsWithoutAReferenceAreAlwaysAccepted(): void
    {
        $body = self::sample();
        unset($body['reference']);
        self::assertSame(201, $this->request('POST', '/transactions', $body)->status);
        self::assertSame(201, $this->request('POST', '/transactions', $body)->status);
        self::assertSame(2, $this->rowCount());
    }

    public function testAnInvalidSubmissionIs422AndStoresNothing(): void
    {
        $response = $this->request('POST', '/transactions', ['amount' => 'lots', 'type' => 'wire']);

        self::assertSame(422, $response->status);
        self::assertSame('validation_failed', $response->body['error']['code']);
        self::assertSame(
            ['amount', 'timestamp', 'description', 'type', 'account_number'],
            array_keys($response->body['error']['fields']),
        );
        self::assertSame(0, $this->rowCount());
    }

    public function testMalformedAndEmptyBodiesAre400(): void
    {
        self::assertSame('invalid_json', $this->request('POST', '/transactions', '{"amount": ')->body['error']['code']);
        self::assertSame('empty_body', $this->request('POST', '/transactions')->body['error']['code']);
        self::assertSame(0, $this->rowCount());
    }

    public function testAnIsoTimestampWithAnOffsetIsStoredInUtc(): void
    {
        $response = $this->request('POST', '/transactions', self::sample(['timestamp' => '2024-05-16T09:22:18+02:00']));

        self::assertSame('2024-05-16T07:22:18.000000Z', $response->body['data']['timestamp']);
    }
}
