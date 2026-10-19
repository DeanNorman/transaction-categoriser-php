<?php
declare(strict_types=1);

namespace App\Http\Handlers;

use App\Http\Request;
use App\Http\Response;
use App\Transactions\TransactionValidator;
use App\Transactions\ValidationException;

/**
 * POST /transactions. Step 2: the request is parsed and validated.
 * Categorisation (step 3) and persistence (step 4) are not here yet, so a
 * valid submission is answered with 501 until they are.
 */
final class SubmitTransaction
{
    public function __construct(private readonly TransactionValidator $validator)
    {
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

        return Response::error(501, 'not_implemented', sprintf(
            'Transaction "%s" is valid; categorisation and storage arrive in the next steps',
            $input->description,
        ));
    }
}
