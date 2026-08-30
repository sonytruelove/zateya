<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Domain\Mechanic\RandomSource;

/**
 * Отдаёт заранее заданную последовательность чисел, зацикливая её.
 * Делает розыгрыш механик полностью предсказуемым в тестах.
 */
final class SequenceRandomSource implements RandomSource
{
    /** @var list<int> */
    private array $values;
    private int $cursor = 0;

    public function __construct(int ...$values)
    {
        $this->values = $values === [] ? [1] : array_values($values);
    }

    public function int(int $min, int $max): int
    {
        $value = $this->values[$this->cursor % count($this->values)];
        $this->cursor++;

        return max($min, min($max, $value));
    }
}
