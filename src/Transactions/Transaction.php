<?php
declare(strict_types=1);

namespace App\Transactions;

use DateTimeImmutable;
use DateTimeZone;

/**
 * A stored transaction: the submitted fields plus the server-minted id and
 * the categorisation it was stored with. This is what every endpoint returns.
 */
final class Transaction
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $reference,
        public readonly string $amount,
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $description,
        public readonly TransactionType $type,
        public readonly string $accountNumber,
        public readonly Category $category,
        public readonly string $categorisationSource,
        public readonly DateTimeImmutable $categorisedAt,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    /** @param array<string,mixed> $row a row of the transactions table */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            reference: $row['reference'] === null ? null : (string) $row['reference'],
            amount: (string) $row['amount'],
            occurredAt: self::utc((string) $row['occurred_at']),
            description: (string) $row['description'],
            type: TransactionType::from((string) $row['type']),
            accountNumber: (string) $row['account_number'],
            category: Category::from((string) $row['category']),
            categorisationSource: (string) $row['categorisation_source'],
            categorisedAt: self::utc((string) $row['categorised_at']),
            createdAt: self::utc((string) $row['created_at']),
        );
    }

    /** The JSON shape of a transaction, identical on POST and both GETs. */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'timestamp' => self::iso($this->occurredAt),
            'description' => $this->description,
            'type' => $this->type->value,
            'account_number' => $this->accountNumber,
            'category' => $this->category->value,
            'categorisation' => [
                'source' => $this->categorisationSource,
                'categorised_at' => self::iso($this->categorisedAt),
            ],
            'created_at' => self::iso($this->createdAt),
        ];
    }

    public static function iso(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
    }

    private static function utc(string $stored): DateTimeImmutable
    {
        return new DateTimeImmutable($stored, new DateTimeZone('UTC'));
    }
}
