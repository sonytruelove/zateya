<?php

declare(strict_types=1);

namespace Src\Application\Shared;

use RuntimeException;

/**
 * Ошибка уровня сценария использования: нарушение предусловия, отсутствие ресурса
 * и т.п. Несёт машиночитаемый код и сообщение на русском.
 */
final class UseCaseException extends RuntimeException
{
    private function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $message): self
    {
        return new self('not_found', $message, 404);
    }

    public static function conflict(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 409);
    }

    public static function unprocessable(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 422);
    }

    public static function forbidden(string $message): self
    {
        return new self('forbidden', $message, 403);
    }
}
