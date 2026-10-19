<?php
declare(strict_types=1);

namespace App\Http;

/**
 * An HTTP request reduced to what the handlers need: method, path, query and
 * a decoded JSON body. Built from PHP's globals in production and by hand in tests.
 */
final class Request
{
    /**
     * @param array<string,string> $query
     * @param array<string,mixed>|null $body  null when there was no body or it was not valid JSON
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly ?array $body = null,
        public readonly bool $bodyWasMalformed = false,
    ) {
    }

    public static function fromGlobals(): self
    {
        $raw = file_get_contents('php://input') ?: '';
        [$body, $malformed] = self::decode($raw);

        $query = [];
        foreach ($_GET as $key => $value) {
            if (is_string($value)) {
                $query[(string) $key] = $value;
            }
        }

        return new self(
            method: strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            path: (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'),
            query: $query,
            body: $body,
            bodyWasMalformed: $malformed,
        );
    }

    /** @return array{0: array<string,mixed>|null, 1: bool} */
    public static function decode(string $raw): array
    {
        if (trim($raw) === '') {
            return [null, false];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [null, true];
        }

        return [$decoded, false];
    }
}
