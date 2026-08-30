<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пропускает запрос к административному интерфейсу только с корректным маркером.
 * Проверка не полагается на умолчание фреймворка: явное сравнение постоянного времени.
 */
final class EnsureAdminToken
{
    public function __construct(private readonly string $expectedToken)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $provided = $this->bearer($request);

        if ($this->expectedToken === '' || $provided === null || !hash_equals($this->expectedToken, $provided)) {
            return response()->json(
                ['error' => ['code' => 'unauthorized', 'message' => 'Требуется маркер организатора.']],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return $next($request);
    }

    private function bearer(Request $request): ?string
    {
        $header = (string) $request->header('Authorization', '');

        return preg_match('/^Bearer\s+(.+)$/i', $header, $m) === 1 ? trim($m[1]) : null;
    }
}
