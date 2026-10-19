<?php
declare(strict_types=1);

namespace App\Transactions;

/** Carries one message per failing field, in the order the fields were checked. */
final class ValidationException extends \RuntimeException
{
    /** @param array<string,string> $fields */
    public function __construct(public readonly array $fields)
    {
        parent::__construct('Validation failed: ' . implode(', ', array_keys($fields)));
    }
}
