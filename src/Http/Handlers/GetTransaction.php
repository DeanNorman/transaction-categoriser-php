<?php
declare(strict_types=1);

namespace App\Http\Handlers;

use App\Http\Request;
use App\Http\Response;
use App\Support\Uuid;
use App\Transactions\TransactionRepository;

/**
 * GET /transactions/{id}. An id that is not even a UUID is a 404 like any
 * other unknown id: the shape of our ids is not something a client is told.
 */
final class GetTransaction
{
    public function __construct(private readonly TransactionRepository $repository)
    {
    }

    /** @param array<string,string> $params */
    public function __invoke(Request $request, array $params): Response
    {
        $id = strtolower($params['id'] ?? '');
        $transaction = Uuid::isValid($id) ? $this->repository->find($id) : null;

        if ($transaction === null) {
            return Response::error(404, 'transaction_not_found', 'No transaction with that id');
        }

        return Response::ok($transaction->toArray());
    }
}
