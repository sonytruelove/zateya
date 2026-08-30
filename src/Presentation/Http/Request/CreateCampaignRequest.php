<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCampaignRequest extends FormRequest
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
            'slug' => ['required', 'string', 'min:3', 'max:64'],
            'title' => ['required', 'string', 'min:3', 'max:140'],
            'mechanic' => ['required', 'string', 'in:quiz,wheel,collection,promo'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'color_hex' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'attempts_per_participant' => ['nullable', 'integer', 'between:1,100'],
            'mechanic_settings' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mechanicSettings(): array
    {
        $settings = $this->input('mechanic_settings', []);

        return is_array($settings) ? $settings : [];
    }
}
