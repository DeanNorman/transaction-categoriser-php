<?php
declare(strict_types=1);

namespace App\Transactions;

/** The ten predefined categories from the brief, in the brief's order. */
enum Category: string
{
    case Groceries = 'Groceries';
    case DiningOut = 'Dining Out';
    case Utilities = 'Utilities';
    case Transportation = 'Transportation';
    case Entertainment = 'Entertainment';
    case Healthcare = 'Healthcare';
    case Shopping = 'Shopping';
    case Housing = 'Housing';
    case Education = 'Education';
    case Miscellaneous = 'Miscellaneous';
}
