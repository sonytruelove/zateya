<?php

declare(strict_types=1);

namespace Src\Domain\Shared;

use RuntimeException;

/**
 * Базовое исключение доменного слоя. Наследники несут конкретный контекст
 * (какая сущность, какие значения), а не общее сообщение.
 */
abstract class DomainException extends RuntimeException
{
}
