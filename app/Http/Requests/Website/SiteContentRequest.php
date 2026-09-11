<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SiteContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'content' => ['required', 'string', 'max:200000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'slug' => 'slug',
            'content' => 'konten',
            'status' => 'status',
            'published_at' => 'tanggal terbit',
            'meta_description' => 'deskripsi meta',
        ];
    }

    protected function uniqueSlugRule(string $table, ?int $ignoreId): array
    {
        return [
            'nullable',
            'string',
            'max:200',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique($table, 'slug')->ignore($ignoreId),
        ];
    }
}
