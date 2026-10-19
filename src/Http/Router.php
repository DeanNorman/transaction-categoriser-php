<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Matches a method and path to a handler. Patterns use `{name}` for a path
 * segment; matched segments are passed to the handler as named strings.
 * Distinguishes "no such path" (404) from "path exists, wrong method" (405).
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = ['method' => strtoupper($method), 'regex' => $regex, 'handler' => $handler];
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return ($route['handler'])($request, $params);
        }

        return $pathMatched
            ? Response::error(405, 'method_not_allowed', 'This route does not accept ' . $request->method)
            : Response::error(404, 'not_found', 'No such route');
    }
}
