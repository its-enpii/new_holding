<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicSite;

use Illuminate\Foundation\Http\FormRequest;

final class SiteMessageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            // Honeypot field hidden from humans; bots tend to fill it.
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'phone' => 'telepon',
            'subject' => 'subjek',
            'message' => 'pesan',
        ];
    }
}
