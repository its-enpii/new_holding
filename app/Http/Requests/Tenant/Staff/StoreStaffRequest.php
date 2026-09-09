<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Staff;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_TENANT_OWNER && $this->user()?->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in([User::ROLE_TENANT_OWNER, User::ROLE_TENANT_STAFF])],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
