<?php
declare(strict_types=1);

namespace App\Http\Handlers;

use App\Http\Request;
use App\Http\Response;
use App\Transactions\Category;
use App\Transactions\Transaction;
use App\Transactions\TransactionRepository;

/**
 * GET /transactions[?category=Groceries]. Newest first, capped at CAP rows
 * so the endpoint cannot be used to dump an unbounded table. Paging is the
 * next step this endpoint would take; see docs/trade-offs.md.
 */
final class ListTransactions
{
    public const CAP = 500;

    public function __construct(private readonly TransactionRepository $repository)
    {
    }

    /** @param array<string,string> $params */
    public function __invoke(Request $request, array $params): Response
    {
        $category = null;
        if (isset($request->query['category']) && $request->query['category'] !== '') {
            $category = Category::tryFrom($request->query['category']);
            if ($category === null) {
                return Response::error(422, 'validation_failed', 'Unknown category', [
                    'category' => 'must be one of ' . implode(', ', array_map(fn (Category $c) => $c->value, Category::cases())),
                ]);
            }
        }

        $transactions = $this->repository->list($category, self::CAP);

        return Response::ok(
            array_map(fn (Transaction $t) => $t->toArray(), $transactions),
            meta: ['count' => count($transactions), 'cap' => self::CAP, 'category' => $category?->value],
        );
    }
}
