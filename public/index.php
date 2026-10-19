<?php
/**
 * Front controller. Every request that is not a static file lands here.
 * Step 1 only answers /health; step 2 introduces the router.
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Db\Connection;

header('Content-Type: application/json');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/health') {
    try {
        Connection::fromEnv()->query('SELECT 1');
        $database = 'ok';
    } catch (\Throwable) {
        $database = 'unavailable';
    }
    http_response_code($database === 'ok' ? 200 : 503);
    echo json_encode(['data' => ['status' => 'ok', 'database' => $database]]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => ['code' => 'not_found', 'message' => 'No such route']]);
