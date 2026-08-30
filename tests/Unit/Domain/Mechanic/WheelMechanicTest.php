<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Mechanic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Mechanic\InvalidMechanicInput;
use Src\Domain\Mechanic\Wheel\WheelMechanic;
use Tests\Support\SequenceRandomSource;

final class WheelMechanicTest extends TestCase
{
    private const SETTINGS = [
        'sectors' => [
            ['label' => 'Пусто', 'weight' => 60, 'winning' => false, 'points' => 1],
            ['label' => 'Приз', 'weight' => 30, 'winning' => true, 'points' => 25],
            ['label' => 'Джекпот', 'weight' => 10, 'winning' => true, 'points' => 100],
        ],
    ];

    #[Test]
    public function it_reports_its_type(): void
    {
        self::assertSame(MechanicType::Wheel, WheelMechanic::fromSettings(self::SETTINGS)->type());
    }

    #[Test]
    public function a_low_roll_lands_on_the_first_sector(): void
    {
        $outcome = WheelMechanic::fromSettings(self::SETTINGS)->play([], new SequenceRandomSource(1));

        self::assertFalse($outcome->won);
        self::assertStringContainsString('Пусто', $outcome->detail);
    }

    #[Test]
    public function a_roll_inside_the_second_band_lands_on_the_prize(): void
    {
        $outcome = WheelMechanic::fromSettings(self::SETTINGS)->play([], new SequenceRandomSource(75));

        self::assertTrue($outcome->won);
        self::assertSame(25, $outcome->score);
    }

    #[Test]
    public function the_highest_roll_lands_on_the_jackpot(): void
    {
        $outcome = WheelMechanic::fromSettings(self::SETTINGS)->play([], new SequenceRandomSource(100));

        self::assertTrue($outcome->won);
        self::assertSame(100, $outcome->score);
    }

    #[Test]
    public function settings_without_sectors_are_rejected(): void
    {
        $this->expectException(InvalidMechanicInput::class);

        WheelMechanic::fromSettings(['sectors' => []]);
    }
}
