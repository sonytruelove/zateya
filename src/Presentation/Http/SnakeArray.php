<?php

declare(strict_types=1);

namespace Src\Presentation\Http;

/**
 * Приводит ключи массива (в том числе вложенного) из «camelCase» к «snake_case».
 * Применяется на границе представления, чтобы формат ответа не зависел от имён
 * свойств объектов передачи данных прикладного слоя.
 */
final class SnakeArray
{
    /**
     * @param array<int|string, mixed> $data
     * @return array<int|string, mixed>
     */
    public static function from(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = is_string($key) ? self::snake($key) : $key;
            $result[$newKey] = is_array($value) ? self::from($value) : $value;
        }

        return $result;
    }

    private static function snake(string $key): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
    }
}
