<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Application\Port\ParticipantSessions;
use Symfony\Component\HttpFoundation\Response;

/**
 * Если предъявлен корректный маркер сессии участника — кладёт его идентификатор
 * в атрибуты запроса. Отсутствие или недействительность маркера не является ошибкой:
 * обработчик просто получит ответ без персональной части.
 */
final class ResolveOptionalParticipant
{
    public function __construct(private readonly ParticipantSessions $sessions)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            $participantId = $this->sessions->resolve(trim($matches[1]));
            if ($participantId !== null) {
                $request->attributes->set(ResolveParticipant::ATTRIBUTE, (string) $participantId);
            }
        }

        return $next($request);
    }
}
