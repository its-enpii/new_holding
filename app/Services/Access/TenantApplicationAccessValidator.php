<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class TenantApplicationAccessValidator
{
    /**
     * Validate that a user may access an application instance.
     *
     * @return array{tenant_name: ?string, application_name: ?string}
     */
    public function validate(User $user, TenantApplication $tenantApplication): array
    {
        if (! $user->isSuperadmin() && $tenantApplication->tenant_id !== $user->tenant_id) {
            throw ValidationException::withMessages([
                'sso' => 'Anda tidak memiliki akses ke aplikasi ini.',
            ]);
        }

        if (! $tenantApplication->is_active) {
            throw ValidationException::withMessages([
                'sso' => 'Aplikasi ini sedang dinonaktifkan oleh administrator.',
            ]);
        }

        if ($tenantApplication->isExpired()) {
            throw ValidationException::withMessages([
                'sso' => 'Masa aktif lisensi aplikasi ini telah berakhir. Silakan hubungi vendor untuk perpanjangan.',
            ]);
        }

        $tenantApplication->loadMissing(['application', 'tenant']);

        return [
            'tenant_name' => $tenantApplication->tenant?->name,
            'application_name' => $tenantApplication->application?->name,
        ];
    }
}
