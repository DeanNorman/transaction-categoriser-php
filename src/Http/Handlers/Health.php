<?php
declare(strict_types=1);

namespace App\Http\Handlers;

use App\Http\Request;
use App\Http\Response;
use PDO;

/** GET /health: is the process up, and can it reach the database? */
final class Health
{
    /** @param callable(): PDO $connect */
    public function __construct(private $connect)
    {
    }

    /** @param array<string,string> $params */
    public function __invoke(Request $request, array $params): Response
    {
        try {
            ($this->connect)()->query('SELECT 1');
            return Response::ok(['status' => 'ok', 'database' => 'ok']);
        } catch (\Throwable) {
            return Response::error(503, 'database_unavailable', 'The database did not answer');
        }
    }
}
