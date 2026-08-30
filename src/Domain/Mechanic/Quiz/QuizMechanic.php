<?php

declare(strict_types=1);

namespace Src\Domain\Mechanic\Quiz;

use Src\Domain\Campaign\MechanicType;
use Src\Domain\Mechanic\InvalidMechanicInput;
use Src\Domain\Mechanic\Mechanic;
use Src\Domain\Mechanic\MechanicOutcome;
use Src\Domain\Mechanic\RandomSource;

/**
 * Викторина: участник отвечает на вопросы, очки — за верные ответы,
 * победа — при наборе порога.
 */
final class QuizMechanic implements Mechanic
{
    public function __construct(private readonly QuizConfig $config)
    {
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function fromSettings(array $settings): self
    {
        $rawQuestions = $settings['questions'] ?? null;
        if (!is_array($rawQuestions) || $rawQuestions === []) {
            throw new InvalidMechanicInput('В настройках викторины нет вопросов.');
        }

        $questions = [];
        foreach (array_values($rawQuestions) as $index => $raw) {
            if (!is_array($raw) || !isset($raw['id'], $raw['correct_option_id'], $raw['points'])) {
                throw new InvalidMechanicInput("Вопрос №{$index}: нужны поля «id», «correct_option_id», «points».");
            }
            $questions[] = new QuizQuestion(
                self::asString($raw['id']),
                self::asString($raw['correct_option_id']),
                self::asInt($raw['points']),
            );
        }

        return new self(new QuizConfig($questions, self::asInt($settings['win_threshold'] ?? 1)));
    }

    public function type(): MechanicType
    {
        return MechanicType::Quiz;
    }

    public function validate(array $payload): void
    {
        foreach ($this->readAnswers($payload) as $answer) {
            if ($this->config->questionById($answer['question_id']) === null) {
                throw new InvalidMechanicInput("Вопрос «{$answer['question_id']}» не входит в эту викторину.");
            }
        }
    }

    public function play(array $payload, RandomSource $random): MechanicOutcome
    {
        $score = 0;
        foreach ($this->readAnswers($payload) as $answer) {
            $question = $this->config->questionById($answer['question_id']);
            $score += $question?->scoreFor($answer['option_id']) ?? 0;
        }

        $detail = "Набрано очков: {$score} из порога {$this->config->winThreshold}.";

        return $score >= $this->config->winThreshold
            ? MechanicOutcome::win($score, $detail)
            : MechanicOutcome::lose($score, $detail);
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array{question_id: string, option_id: string}>
     */
    private function readAnswers(array $payload): array
    {
        $answers = $payload['answers'] ?? null;
        if (!is_array($answers) || $answers === []) {
            throw new InvalidMechanicInput('Ход викторины должен содержать непустой список ответов.');
        }

        $result = [];
        foreach ($answers as $answer) {
            if (!is_array($answer) || !isset($answer['question_id'], $answer['option_id'])) {
                throw new InvalidMechanicInput('Каждый ответ должен содержать «question_id» и «option_id».');
            }
            $result[] = [
                'question_id' => self::asString($answer['question_id']),
                'option_id' => self::asString($answer['option_id']),
            ];
        }

        return $result;
    }

    private static function asString(mixed $value): string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        throw new InvalidMechanicInput('Ожидалась строка или число в данных викторины.');
    }

    private static function asInt(mixed $value): int
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        throw new InvalidMechanicInput('Ожидалось целое число в данных викторины.');
    }
}
