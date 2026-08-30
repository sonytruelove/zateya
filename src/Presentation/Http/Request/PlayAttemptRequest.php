<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class PlayAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'move' => ['sometimes', 'array'],
            'move.answers' => ['sometimes', 'array', 'max:100'],
            'move.answers.*.question_id' => ['required_with:move.answers', 'string', 'max:64'],
            'move.answers.*.option_id' => ['required_with:move.answers', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function movePayload(): array
    {
        $move = $this->input('move', []);

        return is_array($move) ? $move : [];
    }
}
