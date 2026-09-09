<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Staff;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class ResetStaffPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_TENANT_OWNER && $this->user()?->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
