<?php
declare(strict_types=1);

namespace App\Http\Handlers;

use App\Http\Request;
use App\Http\Response;
use App\Categorisation\Categoriser;
use App\Transactions\TransactionValidator;
use App\Transactions\ValidationException;

/**
 * POST /transactions. Steps 2 and 3: the request is parsed, validated and
 * categorised. Persistence (step 4) is not here yet, so a valid submission is
 * answered with 501 that names the category it would have been stored with.
 */
final class SubmitTransaction
{
    public function __construct(
        private readonly TransactionValidator $validator,
        private readonly Categoriser $categoriser,
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

        return Response::error(501, 'not_implemented', sprintf(
            'Transaction "%s" is valid and would be categorised as %s (source: %s); storage arrives in step 4',
            $input->description,
            $categorisation->category->value,
            $categorisation->source,
        ));
    }
}
