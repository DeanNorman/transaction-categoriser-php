<?php
declare(strict_types=1);

namespace App\Transactions;

use App\Categorisation\Categorisation;
use App\Db\Connection;
use App\Support\Clock;
use App\Support\Uuid;
use DateTimeZone;
use PDOException;

/**
 * The only code that reads or writes the transactions table.
 *
 * insert() mints the id and writes the transaction and its categorisation in
 * one statement; the unique key on `reference` is what turns a repeated
 * submission into a DuplicateReferenceException rather than a second row.
 */
final class TransactionRepository
{
    private const MYSQL_DUPLICATE_KEY = 1062;

    public function __construct(
        private readonly Connection $connection,
        private readonly Clock $clock = new Clock(),
    ) {
    }

    public function insert(TransactionInput $input, Categorisation $categorisation): Transaction
    {
        $now = $this->clock->now();
        $transaction = new Transaction(
            id: Uuid::v4(),
            reference: $input->reference,
            amount: $input->amount,
            occurredAt: $input->occurredAt,
            description: $input->description,
            type: $input->type,
            accountNumber: $input->accountNumber,
            category: $categorisation->category,
            categorisationSource: $categorisation->source,
            categorisedAt: $now,
            createdAt: $now,
        );

        $statement = $this->connection->pdo()->prepare(<<<'SQL'
            INSERT INTO transactions
                (id, reference, amount, occurred_at, description, type, account_number,
                 category, categorisation_source, categorised_at, created_at)
            VALUES
                (:id, :reference, :amount, :occurred_at, :description, :type, :account_number,
                 :category, :categorisation_source, :categorised_at, :created_at)
            SQL);

        try {
            $statement->execute([
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'amount' => $transaction->amount,
                'occurred_at' => self::stored($transaction->occurredAt),
                'description' => $transaction->description,
                'type' => $transaction->type->value,
                'account_number' => $transaction->accountNumber,
                'category' => $transaction->category->value,
                'categorisation_source' => $transaction->categorisationSource,
                'categorised_at' => self::stored($transaction->categorisedAt),
                'created_at' => self::stored($transaction->createdAt),
            ]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === self::MYSQL_DUPLICATE_KEY && $input->reference !== null) {
                throw new DuplicateReferenceException($input->reference);
            }
            throw $e;
        }

        return $transaction;
    }

    private static function stored(\DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
    }
}
