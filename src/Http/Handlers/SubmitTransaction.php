<?php
declare(strict_types=1);

namespace App\Http\Handlers;

use App\Categorisation\Categoriser;
use App\Http\Request;
use App\Http\Response;
use App\Transactions\DuplicateReferenceException;
use App\Transactions\TransactionRepository;
use App\Transactions\TransactionValidator;
use App\Transactions\ValidationException;

/**
 * POST /transactions: parse, validate, categorise, store, answer 201 with the
 * stored transaction. The whole journey of a single submission is these
 * four calls, in this order.
 */
final class SubmitTransaction
{
    public function __construct(
        private readonly TransactionValidator $validator,
        private readonly Categoriser $categoriser,
        private readonly TransactionRepository $repository,
    ) {
    }

    /** @param array<string,string> $params */
    public function __invoke(Request $request, array $params): Response
    {
        if ($request->bodyWasMalformed) {
            return Response::error(400, 'invalid_json', 'The request body is not valid JSON');
        }
        if ($request->body === null) {
            return Response::error(400, 'empty_body', 'A JSON body is required');
        }

        try {
            $input = $this->validator->validate($request->body);
        } catch (ValidationException $e) {
            return Response::error(422, 'validation_failed', 'One or more fields are invalid', $e->fields);
        }

        $categorisation = $this->categoriser->categorise($input->description);

        try {
            $transaction = $this->repository->insert($input, $categorisation);
        } catch (DuplicateReferenceException $e) {
            return Response::error(409, 'duplicate_reference', $e->getMessage(), ['reference' => 'already exists']);
        }

        return Response::ok($transaction->toArray(), 201);
    }
}
