<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Campaign\CampaignStatus;
use Src\Domain\Campaign\CampaignTheme;
use Src\Domain\Campaign\InvalidCampaignTheme;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Mechanic\InvalidMechanicInput;
use Src\Domain\Mechanic\Quiz\QuizConfig;
use Src\Domain\Mechanic\Quiz\QuizQuestion;
use Src\Domain\Mechanic\SystemRandomSource;
use Src\Domain\Mechanic\Wheel\WheelConfig;
use Src\Domain\Mechanic\Wheel\WheelSector;
use Src\Domain\Participation\Channel;
use Src\Domain\Participation\ChannelIdentity;
use Src\Domain\Participation\InvalidChannelIdentity;
use Src\Domain\Participation\UnknownChannel;

final class ValueObjectGuardsTest extends TestCase
{
    #[Test]
    public function channel_maps_external_values_and_titles_and_rejects_the_unknown(): void
    {
        self::assertSame(Channel::Telegram, Channel::fromExternal(' Telegram '));
        self::assertSame('VK', Channel::Vk->title());
        self::assertSame('Веб', Channel::Web->title());

        $this->expectException(UnknownChannel::class);
        Channel::fromExternal('pager');
    }

    #[Test]
    public function channel_identity_rejects_an_empty_or_overlong_external_id(): void
    {
        self::assertSame('telegram:55', ChannelIdentity::of(Channel::Telegram, '55')->fingerprint());

        $this->expectException(InvalidChannelIdentity::class);
        ChannelIdentity::of(Channel::Web, '');
    }

    #[Test]
    public function campaign_theme_rejects_a_bad_colour(): void
    {
        $this->expectException(InvalidCampaignTheme::class);
        CampaignTheme::of('blue', 'X');
    }

    #[Test]
    public function campaign_theme_rejects_an_empty_emoji(): void
    {
        $this->expectException(InvalidCampaignTheme::class);
        CampaignTheme::of('#0b57d0', '   ');
    }

    #[Test]
    public function every_campaign_status_and_mechanic_type_has_a_russian_title(): void
    {
        foreach (CampaignStatus::cases() as $status) {
            self::assertNotSame('', $status->title());
        }
        foreach (MechanicType::cases() as $type) {
            self::assertNotSame('', $type->title());
        }

        self::assertFalse(MechanicType::Collection->isImplemented());
        self::assertTrue(MechanicType::Quiz->isImplemented());
    }

    #[Test]
    public function quiz_question_requires_positive_points(): void
    {
        $this->expectException(InvalidMechanicInput::class);
        new QuizQuestion('q1', 'a', 0);
    }

    #[Test]
    public function quiz_config_requires_a_positive_win_threshold(): void
    {
        $this->expectException(InvalidMechanicInput::class);
        new QuizConfig([new QuizQuestion('q1', 'a', 5)], 0);
    }

    #[Test]
    public function wheel_sector_rejects_a_non_positive_weight(): void
    {
        $this->expectException(InvalidMechanicInput::class);
        new WheelSector('X', 0, false, 0);
    }

    #[Test]
    public function wheel_config_totals_the_sector_weights(): void
    {
        $config = new WheelConfig([
            new WheelSector('A', 2, false, 0),
            new WheelSector('B', 3, true, 10),
        ]);

        self::assertSame(5, $config->totalWeight());
    }

    #[Test]
    public function system_random_source_rejects_an_inverted_range(): void
    {
        $this->expectException(InvalidMechanicInput::class);
        (new SystemRandomSource())->int(10, 1);
    }

    #[Test]
    public function system_random_source_returns_a_value_inside_the_range(): void
    {
        $value = (new SystemRandomSource())->int(3, 3);
        self::assertSame(3, $value);
    }
}
