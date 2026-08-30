<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class OpenSessionRequest extends FormRequest
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
            'channel' => ['required', 'string', 'in:web,telegram,vk'],
            'campaign_slug' => ['required', 'string', 'max:64'],
            'channel_token' => ['required', 'string', 'max:128'],
            'display_name' => ['nullable', 'string', 'max:60'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('display_name') || $this->input('display_name') === null) {
            $this->merge(['display_name' => 'Участник']);
        }
    }
}
