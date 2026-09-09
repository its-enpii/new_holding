<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Staff;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_TENANT_OWNER && $this->user()?->tenant_id !== null;
    }

    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('staff');
        $targetUserId = $targetUser instanceof User ? $targetUser->id : (int) $targetUser;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($targetUserId)],
            'role' => ['required', 'string', Rule::in([User::ROLE_TENANT_OWNER, User::ROLE_TENANT_STAFF])],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
