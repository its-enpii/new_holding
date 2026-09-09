<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:applications,slug'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon_path' => ['nullable', 'string', 'max:255'],
            'base_url' => ['required', 'string', 'url', 'max:255'],
            'has_financial_report' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
