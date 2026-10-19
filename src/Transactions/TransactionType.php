<?php
declare(strict_types=1);

namespace App\Transactions;

/** The bank's word for the direction of a transaction. Stored as given. */
enum TransactionType: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
