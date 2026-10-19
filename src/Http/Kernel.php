<?php
declare(strict_types=1);

namespace App\Http;

use App\Categorisation\RuleBasedCategoriser;
use App\Db\Connection;
use App\Http\Handlers\Health;
use App\Http\Handlers\SubmitTransaction;
use App\Transactions\TransactionRepository;
use App\Transactions\TransactionValidator;

/**
 * Wires the routes to their handlers and turns any uncaught failure into a
 * JSON 500. The front controller calls handle(); so do the integration tests,
 * which is why nothing here touches PHP's globals.
 */
final class Kernel
{
    private Router $router;

    public function __construct(Connection $connection)
    {
        $validator = new TransactionValidator();
        $categoriser = RuleBasedCategoriser::withDefaultRules();
        $repository = new TransactionRepository($connection);

        $this->router = new Router();
        $this->router->add('GET', '/health', new Health($connection));
        $this->router->add('POST', '/transactions', new SubmitTransaction($validator, $categoriser, $repository));
    }

    public static function fromEnv(): self
    {
        return new self(Connection::fromEnv());
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (\Throwable $e) {
            error_log(sprintf('[kernel] %s: %s', $e::class, $e->getMessage()));

            return Response::error(500, 'internal_error', 'Something went wrong on our side');
        }
    }
}
