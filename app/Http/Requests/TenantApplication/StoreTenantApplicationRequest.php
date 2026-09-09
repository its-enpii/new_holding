<?php

declare(strict_types=1);

namespace App\Http\Requests\TenantApplication;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTenantApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    public function rules(): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');
        $tenantId = $tenant instanceof Tenant ? $tenant->id : (int) $tenant;

        return [
            'application_id' => [
                'required',
                'integer',
                'exists:applications,id',
                Rule::unique('tenant_applications', 'application_id')->where('tenant_id', $tenantId),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'instance_url' => ['required', 'string', 'url', 'max:255'],
            'expired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
