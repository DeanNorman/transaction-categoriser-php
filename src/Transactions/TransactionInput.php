<?php
declare(strict_types=1);

namespace App\Transactions;

use DateTimeImmutable;

/**
 * A submitted transaction after validation: every field present, typed and
 * trimmed. This is the only shape the categoriser and the repository accept,
 * so neither needs to re-check input.
 */
final class TransactionInput
{
    public function __construct(
        public readonly ?string $reference,
        public readonly string $amount,          // decimal string with two places, sign kept
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $description,
        public readonly TransactionType $type,
        public readonly string $accountNumber,
    ) {
    }
}
