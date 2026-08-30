<?php

declare(strict_types=1);

namespace Src\Presentation\Http;

use Illuminate\Http\JsonResponse;
use Src\Application\Shared\UseCaseException;
use Src\Domain\Shared\DomainException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Превращает исключения прикладного и доменного слоёв в единый формат ответа
 * `{ "error": { "code": ..., "message": ... } }`. Технические детали не раскрываются.
 */
final class ApiExceptionPresenter
{
    public function fromUseCase(UseCaseException $exception): JsonResponse
    {
        return $this->body($exception->errorCode, $exception->getMessage(), $exception->httpStatus);
    }

    public function fromDomain(DomainException $exception): JsonResponse
    {
        return $this->body('domain_rule_violated', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function body(string $code, string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
