<?php

declare(strict_types=1);

namespace App\Http\Requests\TenantApplication;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'instance_url' => ['required', 'string', 'url', 'max:255'],
            'expired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
