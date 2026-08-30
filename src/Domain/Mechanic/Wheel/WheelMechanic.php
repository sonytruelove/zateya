<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic\Wheel;

use Src\Domain\Campaign\MechanicType;
use Src\Domain\Mechanic\InvalidMechanicInput;
use Src\Domain\Mechanic\Mechanic;
use Src\Domain\Mechanic\MechanicOutcome;
use Src\Domain\Mechanic\RandomSource;

/**
 * Колесо фортуны: сектор выбирается взвешенным случайным выбором.
 */
final class WheelMechanic implements Mechanic
{
    public function __construct(private readonly WheelConfig $config)
    {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function fromSettings(array $settings): self
    {
        $rawSectors = $settings['sectors'] ?? null;
        if (!is_array($rawSectors) || $rawSectors === []) {
            throw new InvalidMechanicInput('В настройках колеса фортуны нет секторов.');
        }

        $sectors = [];
        foreach (array_values($rawSectors) as $index => $raw) {
            if (!is_array($raw) || !isset($raw['label'], $raw['weight'])) {
                throw new InvalidMechanicInput("Сектор №{$index}: нужны поля «label» и «weight».");
            }
            $sectors[] = new WheelSector(
                self::asString($raw['label']),
                self::asInt($raw['weight']),
                (bool) ($raw['winning'] ?? false),
                self::asInt($raw['points'] ?? 0),
            );
        }

        return new self(new WheelConfig($sectors));
    }

    public function type(): MechanicType
    {
        return MechanicType::Wheel;
    }

    public function validate(array $payload): void
    {
        // Колесо не требует данных хода от участника.
    }

    public function play(array $payload, RandomSource $random): MechanicOutcome
    {
        $pick = $random->int(1, $this->config->totalWeight());
        $cursor = 0;

        foreach ($this->config->sectors as $sector) {
            $cursor += $sector->weight;
            if ($pick <= $cursor) {
                return $this->outcomeFor($sector);
            }
        }

        throw new InvalidMechanicInput('Не удалось выбрать сектор колеса: проверьте веса секторов.');
    }

    private function outcomeFor(WheelSector $sector): MechanicOutcome
    {
        $detail = "Выпал сектор «{$sector->label}».";

        return $sector->winning
            ? MechanicOutcome::win($sector->points, $detail)
            : MechanicOutcome::lose($sector->points, $detail);
    }

    private static function asString(mixed $value): string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        throw new InvalidMechanicInput('Ожидалась строка в настройках колеса фортуны.');
    }

    private static function asInt(mixed $value): int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        throw new InvalidMechanicInput('Ожидалось целое число в настройках колеса фортуны.');
    }
}
