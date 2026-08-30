<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Приём промокодов двумя способами: список строк в поле «codes» либо файл CSV/текст
 * в поле «file». Файл разбирается в памяти по строкам, не сохраняется и не отдаётся обратно;
 * тип ограничен списком разрешённых расширений и объёмом.
 */
final class UploadPromoCodesRequest extends FormRequest
{
    private const MAX_FILE_KB = 1024;

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
            'codes' => ['required_without:file', 'array', 'max:50000'],
            'codes.*' => ['string', 'max:64'],
            'file' => ['required_without:codes', 'file', 'mimes:csv,txt', 'extensions:csv,txt', 'max:' . self::MAX_FILE_KB],
        ];
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        $fromField = $this->input('codes');
        if (is_array($fromField)) {
            return array_values(array_map(static fn ($v): string => (string) $v, $fromField));
        }

        $file = $this->file('file');
        if ($file === null) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $file->get()) ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));
    }
}
