<?php
declare(strict_types=1);

namespace App\Transactions;

/** A transaction with this reference is already stored. */
final class DuplicateReferenceException extends \RuntimeException
{
    public function __construct(public readonly string $reference)
    {
        parent::__construct("A transaction with reference $reference already exists");
    }
}
