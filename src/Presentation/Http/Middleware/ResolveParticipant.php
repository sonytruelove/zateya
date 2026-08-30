<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Application\Port\ParticipantSessions;
use Symfony\Component\HttpFoundation\Response;

/**
 * Определяет участника по непрозрачному маркеру сессии и кладёт его идентификатор
 * в атрибуты запроса. Без валидного маркера — 401.
 */
final class ResolveParticipant
{
    public const ATTRIBUTE = 'participant_id';

    public function __construct(private readonly ParticipantSessions $sessions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->bearer($request);
        $participantId = $token !== null ? $this->sessions->resolve($token) : null;

        if ($participantId === null) {
            return response()->json(
                ['error' => ['code' => 'unauthorized', 'message' => 'Требуется маркер сессии участника.']],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $request->attributes->set(self::ATTRIBUTE, (string) $participantId);

        return $next($request);
    }

    private function bearer(Request $request): ?string
    {
        $header = (string) $request->header('Authorization', '');

        return preg_match('/^Bearer\s+(.+)$/i', $header, $m) === 1 ? trim($m[1]) : null;
    }
}
