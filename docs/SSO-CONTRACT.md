# Holding SSO Contract

## Architecture

The holding portal exchanges a one-time token for a session in a subsidiary application (sidbm, lkm, or akubumdes). The flow is one-way:

1. A superadmin or tenant owner clicks **Buka Aplikasi**.
2. Holding validates the user, tenant, active license, and expiration.
3. Holding creates a random 64-character hexadecimal token and stores its SHA-256 hash as `sso:{hash}` in cache for 60 seconds.
4. Holding redirects to `{instance_url}/auth/holding?token={plainToken}`.
5. The subsidiary verifies the token, consumes the cache entry once, resolves the target sub-tenant, logs the user in or creates the user, and redirects to its dashboard.

The shared secret (`HOLDING_SSO_SECRET`) is optional. When configured and identical in both applications, Holding adds an HMAC signature for the cache payload; the subsidiary must verify the signature before consuming the cache entry.

## Payload

The cache payload is JSON:

```json
{
    "tenant_application_id": 12,
    "user_id": 34,
    "email": "owner@example.test",
    "name": "Owner Tenant",
    "role": "tenant_owner",
    "tenant_name": "BUMDesma Contoh",
    "sub_tenant_code": "sukamaju",
    "exp": 1787884800,
    "signature": "optional HMAC SHA-256 of the JSON payload without signature"
}
```

`sub_tenant_code` is always included. It is `null` when the license is not scoped to a code inside the subsidiary application.

## Holding Configuration

```env
HOLDING_SSO_SECRET=
```

For a development-only setup, leave it empty. In production, set the same non-empty secret in Holding and every subsidiary application.

## Subsidiary Laravel Implementation

After token verification and signature validation, the subsidiary **MUST** activate the tenant context identified by `sub_tenant_code` before authenticating the user. If the value is `null`, retain the application's existing/default tenant behavior. When the code is present, resolve that exact tenant and bind the created or authenticated user to that tenant; do not fall back silently to the default tenant.

In `new_sidbm`, the resolved code may be used directly through the application's tenant resolver, or forwarded as `X-Tenant-Code: {sub_tenant_code}` when constructing the subsequent route/request through `App\Tenancy\Middleware\ResolveTenant`. The contract does not require a particular subsidiary implementation—only that the correct tenant context is active before login/user binding.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Tenancy\TenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

final class HoldingSsoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token', '');

        try {
            $payload = $this->consumePayload($token);
            $tenant = $this->resolveTenant($payload);
            $user = User::query()->updateOrCreate(
                ['email' => $payload['email']],
                [
                    'name' => $payload['name'],
                    'role' => $payload['role'],
                    'password' => Hash::make(bin2hex(random_bytes(32))),
                ]
            );
        } catch (\Throwable) {
            return redirect()
                ->route('login')
                ->with('error', 'Sesi SSO tidak valid atau sudah kedaluwarsa.');
        }

        $request->session()->regenerate();
        auth()->login($user);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveTenant(array $payload): Tenant
    {
        if (! array_key_exists('sub_tenant_code', $payload)) {
            throw new \RuntimeException('Invalid SSO payload contract.');
        }

        $subTenantCode = $payload['sub_tenant_code'];

        if ($subTenantCode === null) {
            return TenantResolver::default();
        }

        $tenant = TenantResolver::resolveByCode((string) $subTenantCode);

        if ($tenant === null) {
            throw new \RuntimeException('Sub-tenant was not found.');
        }

        return $tenant;
    }

    /**
     * @return array<string, mixed>
     */
    private function consumePayload(string $token): array
    {
        if ($token === '') {
            throw new \InvalidArgumentException('Token is required.');
        }

        $cacheKey = 'sso:'.hash('sha256', $token);
        /** @var array<string, mixed>|null $payload */
        $payload = Cache::pull($cacheKey);

        if ($payload === null || (int) $payload['exp'] < time()) {
            throw new \RuntimeException('Invalid or expired SSO token.');
        }

        $secret = (string) config('services.holding_sso.secret');
        if ($secret !== '') {
            $signature = (string) $payload['signature'];
            unset($payload['signature']);
            $expected = hash_hmac('sha256', json_encode($payload), $secret);

            if (! hash_equals($expected, $signature)) {
                throw new \RuntimeException('Invalid SSO signature.');
            }
        }

        return $payload;
    }
}
```

Add the route outside the authenticated middleware:

```php
Route::get('/auth/holding', [HoldingSsoController::class, 'store'])
    ->middleware(['web', 'throttle:10,1'])
    ->name('auth.holding');
```

Set the same configuration:

```php
'holding_sso' => [
    'secret' => env('HOLDING_SSO_SECRET'),
],
```

## Security Rules

- Cache entries are consumed with `Cache::pull`; a token can only be used once.
- Token lifetime is 60 seconds.
- Expired payloads must never create a session.
- Signature verification happens before cache consumption.
- On any failure, redirect to the subsidiary login page with a generic flash error.
- The user role in the payload is authoritative for the subsidiary, while tenant ownership is enforced by the application instance itself.
