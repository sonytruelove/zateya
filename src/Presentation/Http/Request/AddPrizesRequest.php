<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class AddPrizesRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:2', 'max:120'],
            'quantity' => ['required', 'integer', 'between:1,1000000'],
        ];
    }
}
