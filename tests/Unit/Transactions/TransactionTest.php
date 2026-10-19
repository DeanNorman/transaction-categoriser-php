<?php
declare(strict_types=1);

namespace Tests\Unit\Transactions;

use App\Transactions\Transaction;
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    public function testARowBecomesTheApiShape(): void
    {
        $transaction = Transaction::fromRow([
            'id' => '0f8fad5b-d9cb-4d4a-9f2a-6d1e1c2b3a4f',
            'reference' => 'TXN00001',
            'amount' => '-87.18',
            'occurred_at' => '2024-05-16 07:22:18.808433',
            'description' => 'Municipal Tax Payment',
            'type' => 'debit',
            'account_number' => 'NLINGB1944573686',
            'category' => 'Housing',
            'categorisation_source' => 'housing.taxes',
            'categorised_at' => '2026-10-19 14:00:00.000000',
            'created_at' => '2026-10-19 14:00:00.000000',
        ]);

        self::assertSame([
            'id' => '0f8fad5b-d9cb-4d4a-9f2a-6d1e1c2b3a4f',
            'reference' => 'TXN00001',
            'amount' => '-87.18',
            'timestamp' => '2024-05-16T07:22:18.808433Z',
            'description' => 'Municipal Tax Payment',
            'type' => 'debit',
            'account_number' => 'NLINGB1944573686',
            'category' => 'Housing',
            'categorisation' => [
                'source' => 'housing.taxes',
                'categorised_at' => '2026-10-19T14:00:00.000000Z',
            ],
            'created_at' => '2026-10-19T14:00:00.000000Z',
        ], $transaction->toArray());
    }

    public function testANullReferenceStaysNull(): void
    {
        $transaction = Transaction::fromRow([
            'id' => 'x', 'reference' => null, 'amount' => '1.00', 'occurred_at' => '2024-01-01 00:00:00',
            'description' => 'd', 'type' => 'credit', 'account_number' => 'a', 'category' => 'Miscellaneous',
            'categorisation_source' => 'fallback', 'categorised_at' => '2024-01-01 00:00:00', 'created_at' => '2024-01-01 00:00:00',
        ]);
        self::assertNull($transaction->toArray()['reference']);
    }
}
