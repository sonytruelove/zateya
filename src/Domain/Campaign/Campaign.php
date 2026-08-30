<?php

declare(strict_types=1);

namespace Src\Domain\Campaign;

use DateTimeImmutable;
use Src\Domain\Event\CampaignPublished;
use Src\Domain\Event\DomainEvent;

/**
 * Агрегат «Кампания»: правила жизненного цикла и признак приёма попыток.
 * Конфигурация механики хранится отдельно, здесь — только её тип.
 */
final class Campaign
{
    /** @var list<DomainEvent> */
    private array $events = [];

    private function __construct(
        public readonly CampaignId $id,
        public readonly Slug $slug,
        private string $title,
        public readonly MechanicType $mechanic,
        private CampaignPeriod $period,
        private CampaignTheme $theme,
        private int $attemptsPerParticipant,
        private CampaignStatus $status,
    ) {
    }

    public static function createDraft(
        CampaignId $id,
        Slug $slug,
        string $title,
        MechanicType $mechanic,
        CampaignPeriod $period,
        CampaignTheme $theme,
        int $attemptsPerParticipant,
    ): self {
        self::guardTitle($title);
        self::guardAttempts($attemptsPerParticipant);

        return new self($id, $slug, trim($title), $mechanic, $period, $theme, $attemptsPerParticipant, CampaignStatus::Draft);
    }

    /**
     * @param array{id:string,slug:string,title:string,mechanic:string,starts_at:string,ends_at:string,color_hex:string,emoji:string,attempts:int,status:string} $row
     */
    public static function fromState(array $row): self
    {
        return new self(
            CampaignId::fromString($row['id']),
            Slug::fromString($row['slug']),
            $row['title'],
            MechanicType::from($row['mechanic']),
            CampaignPeriod::between(new DateTimeImmutable($row['starts_at']), new DateTimeImmutable($row['ends_at'])),
            CampaignTheme::of($row['color_hex'], $row['emoji']),
            $row['attempts'],
            CampaignStatus::from($row['status']),
        );
    }

    public function publish(DateTimeImmutable $now): void
    {
        $this->ensureCurrent(CampaignStatus::Draft, CampaignStatus::Scheduled);

        if (!$this->mechanic->isImplemented()) {
            throw new CampaignTransitionNotAllowed(
                "Механика «{$this->mechanic->title()}» ещё не поддерживается для публикации.",
            );
        }

        $this->status = $this->period->startsInFuture($now) ? CampaignStatus::Scheduled : CampaignStatus::Active;
        $this->events[] = new CampaignPublished((string) $this->id, (string) $this->slug, $now);
    }

    public function activate(DateTimeImmutable $now): void
    {
        $this->ensureCurrent(CampaignStatus::Scheduled);

        if ($this->period->startsInFuture($now)) {
            throw new CampaignTransitionNotAllowed('Кампанию нельзя активировать раньше начала периода.');
        }

        $this->status = CampaignStatus::Active;
    }

    public function pause(): void
    {
        $this->ensureCurrent(CampaignStatus::Active);
        $this->status = CampaignStatus::Paused;
    }

    public function resume(): void
    {
        $this->ensureCurrent(CampaignStatus::Paused);
        $this->status = CampaignStatus::Active;
    }

    public function finish(): void
    {
        $this->ensureCurrent(CampaignStatus::Active, CampaignStatus::Paused, CampaignStatus::Scheduled);
        $this->status = CampaignStatus::Finished;
    }

    public function archive(): void
    {
        $this->ensureCurrent(CampaignStatus::Finished);
        $this->status = CampaignStatus::Archived;
    }

    public function isAcceptingAttempts(DateTimeImmutable $now): bool
    {
        return $this->status === CampaignStatus::Active && $this->period->contains($now);
    }

    public function rename(string $title): void
    {
        self::guardTitle($title);
        $this->title = trim($title);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function status(): CampaignStatus
    {
        return $this->status;
    }

    public function period(): CampaignPeriod
    {
        return $this->period;
    }

    public function theme(): CampaignTheme
    {
        return $this->theme;
    }

    public function attemptsPerParticipant(): int
    {
        return $this->attemptsPerParticipant;
    }

    /** @return list<DomainEvent> */
    public function releaseEvents(): array
    {
        $released = $this->events;
        $this->events = [];

        return $released;
    }

    private function ensureCurrent(CampaignStatus ...$allowed): void
    {
        if (!in_array($this->status, $allowed, true)) {
            throw CampaignTransitionNotAllowed::between($this->status, $allowed[0]);
        }
    }

    private static function guardTitle(string $title): void
    {
        $length = mb_strlen(trim($title));
        if ($length < 3 || $length > 140) {
            throw new CampaignTransitionNotAllowed("Название кампании: длина {$length}, допустимо от 3 до 140 символов.");
        }
    }

    private static function guardAttempts(int $attempts): void
    {
        if ($attempts < 1 || $attempts > 100) {
            throw new CampaignTransitionNotAllowed("Число попыток на участника {$attempts} вне диапазона 1..100.");
        }
    }
}
