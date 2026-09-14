<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class SsoTokenService
{
    /**
     * Payload exchanged with a subsidiary application.
     *
     * @return array{
     *     tenant_application_id: int,
     *     user_id: int,
     *     email: string,
     *     name: string,
     *     role: string,
     *     tenant_name: ?string,
     *     sub_tenant_code: ?string,
     *     exp: int
     * }
     */
    public function create(TenantApplication $tenantApplication, User $user): array
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $tenantName = $tenantApplication->tenant?->name;
        $expiresAt = now()->addMinute();
        $payload = [
            'tenant_application_id' => $tenantApplication->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'tenant_name' => $tenantName,
            'sub_tenant_code' => $tenantApplication->sub_tenant_code,
            'exp' => $expiresAt->timestamp,
        ];

        $secret = (string) config('services.holding_sso.secret');
        if ($secret !== '') {
            $payload['signature'] = hash_hmac('sha256', json_encode($payload), $secret);
        }

        Cache::store('sso')->put(
            "sso:{$tokenHash}",
            $payload,
            $expiresAt
        );

        return [
            'token' => $plainToken,
            'payload' => $payload,
        ];
    }
}
