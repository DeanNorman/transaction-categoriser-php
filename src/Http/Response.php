<?php
declare(strict_types=1);

namespace App\Http;

/**
 * A JSON response the kernel returns and the front controller emits.
 * Every success body has a top-level `data`; every failure a top-level `error`.
 */
final class Response
{
    /** @param array<string,mixed> $body */
    private function __construct(
        public readonly int $status,
        public readonly array $body,
    ) {
    }

    /** @param array<string,mixed>|null $meta */
    public static function ok(mixed $data, int $status = 200, ?array $meta = null): self
    {
        $body = ['data' => $data];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return new self($status, $body);
    }

    /** @param array<string,string>|null $fields */
    public static function error(int $status, string $code, string $message, ?array $fields = null): self
    {
        $error = ['code' => $code, 'message' => $message];
        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        return new self($status, ['error' => $error]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json');
        echo json_encode($this->body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
