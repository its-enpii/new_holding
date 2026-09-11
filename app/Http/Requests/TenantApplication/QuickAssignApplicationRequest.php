<?php

namespace App\Http\Requests\TenantApplication;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QuickAssignApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() === true;
    }

    public function rules(): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');

        return [
            'application_id' => [
                'required',
                'integer',
                Rule::exists('applications', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }
}
