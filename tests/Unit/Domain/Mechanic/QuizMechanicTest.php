<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Mechanic;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Src\Domain\Campaign\MechanicType;
use Src\Domain\Mechanic\InvalidMechanicInput;
use Src\Domain\Mechanic\Quiz\QuizMechanic;
use Tests\Support\SequenceRandomSource;

final class QuizMechanicTest extends TestCase
{
    private const SETTINGS = [
        'win_threshold' => 20,
        'questions' => [
            ['id' => 'q1', 'correct_option_id' => 'a', 'points' => 10],
            ['id' => 'q2', 'correct_option_id' => 'b', 'points' => 10],
            ['id' => 'q3', 'correct_option_id' => 'c', 'points' => 10],
        ],
    ];

    #[Test]
    public function it_reports_its_type(): void
    {
        self::assertSame(MechanicType::Quiz, $this->mechanic()->type());
    }

    #[Test]
    public function reaching_the_threshold_is_a_win(): void
    {
        $outcome = $this->mechanic()->play([
            'answers' => [
                ['question_id' => 'q1', 'option_id' => 'a'],
                ['question_id' => 'q2', 'option_id' => 'b'],
            ],
        ], new SequenceRandomSource(1));

        self::assertTrue($outcome->won);
        self::assertSame(20, $outcome->score);
    }

    #[Test]
    public function staying_below_the_threshold_is_a_loss(): void
    {
        $outcome = $this->mechanic()->play([
            'answers' => [
                ['question_id' => 'q1', 'option_id' => 'a'],
                ['question_id' => 'q2', 'option_id' => 'wrong'],
            ],
        ], new SequenceRandomSource(1));

        self::assertFalse($outcome->won);
        self::assertSame(10, $outcome->score);
    }

    #[Test]
    public function an_empty_answer_list_is_rejected(): void
    {
        $this->expectException(InvalidMechanicInput::class);

        $this->mechanic()->validate(['answers' => []]);
    }

    #[Test]
    public function an_unknown_question_is_rejected(): void
    {
        $this->expectException(InvalidMechanicInput::class);
        $this->expectExceptionMessage('q9');

        $this->mechanic()->validate(['answers' => [['question_id' => 'q9', 'option_id' => 'a']]]);
    }

    #[Test]
    public function settings_without_questions_are_rejected(): void
    {
        $this->expectException(InvalidMechanicInput::class);

        QuizMechanic::fromSettings(['questions' => []]);
    }

    private function mechanic(): QuizMechanic
    {
        return QuizMechanic::fromSettings(self::SETTINGS);
    }
}
