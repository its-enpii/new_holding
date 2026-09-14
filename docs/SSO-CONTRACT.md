# Holding SSO Contract

## Architecture

The holding portal exchanges a one-time token for a session in a subsidiary application (sidbm, lkm, or akubumdes). The flow is one-way:

1. A superadmin or tenant owner clicks **Buka Aplikasi**.
2. Holding validates the user, tenant, active license, and expiration.
3. Holding creates a random 64-character hexadecimal token and stores its SHA-256 hash as `sso:{hash}` in the dedicated `sso` cache store for 60 seconds.
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

## Shared SSO Cache

The token bus is a **dedicated cache store named `sso`**, not the application default store. The default store differs per application (Holding uses `database`, subsidiaries commonly use their own `redis`), so sharing it would make the token invisible to the receiver.

Holding (`config/cache.php`, `config/database.php`):

```php
'sso' => [
    'driver' => 'redis',
    'connection' => env('SSO_CACHE_REDIS_CONNECTION', 'sso'),
    'lock_connection' => env('SSO_CACHE_LOCK_CONNECTION', 'sso'),
    'prefix' => env('SSO_CACHE_PREFIX', ''),
],

'redis' => [
    // ...
    'sso' => [
        'host' => env('SSO_REDIS_HOST', '127.0.0.1'),
        'password' => env('SSO_REDIS_PASSWORD', ''),
        'port' => env('SSO_REDIS_PORT', '6380'),
        'database' => env('SSO_REDIS_DB', '0'),
        'prefix' => env('SSO_REDIS_PREFIX', ''),
    ],
],
```

Issuer and consumer:

```php
Cache::store('sso')->put("sso:{$tokenHash}", $payload, $expiresAt); // Holding
$payload = Cache::store('sso')->pull('sso:'.hash('sha256', $token)); // subsidiary
```

### Key layout — keep prefixes empty

The Redis key must be **exactly** `sso:{sha256(token)}` in every application on the bus. Both `cache.stores.sso.prefix` and `database.redis.sso.prefix` therefore default to an empty string; the per-application `CACHE_PREFIX` / `REDIS_PREFIX` values must not be applied to the SSO store, otherwise each application reads a different key.

### Environment variables

| Variable | Purpose | Default |
| --- | --- | --- |
| `SSO_REDIS_HOST` | Redis host of the central SSO bus | `127.0.0.1` |
| `SSO_REDIS_PORT` | Redis port of the central SSO bus | `6380` |
| `SSO_REDIS_PASSWORD` | `requirepass` value of the bus (empty = no AUTH) | empty |
| `SSO_REDIS_DB` | Redis logical database on the bus | `0` |
| `SSO_CACHE_REDIS_CONNECTION` | Redis connection used by the `sso` store | `sso` |
| `SSO_CACHE_PREFIX` | Escape hatch; must stay empty for cross-app reads | empty |

Nothing is hardcoded: no docker service name, container hostname, or fixed port may appear in application code. All coordinates come from the environment so one build runs in either deployment mode.

### Deployment mode 1 — same server

All applications run on one host. Every app points at the same loopback endpoint exposed by the Holding compose stack:

```env
SSO_REDIS_HOST=127.0.0.1
SSO_REDIS_PORT=6380
SSO_REDIS_PASSWORD=<same value as holding>
```

### Deployment mode 2 — different servers

The applications live on separate hosts, so the bus must be reachable from outside the Holding server. Point the subsidiaries at Holding's public IP/DNS; the port is published by the Holding compose file and protected by `requirepass`:

```env
SSO_REDIS_HOST=sso-holding.example.com   # IP/DNS of the holding server
SSO_REDIS_PORT=6380
SSO_REDIS_PASSWORD=<same value as holding>
```

Firewall the bus port to the subsidiary servers only (never expose an unprotected Redis), and prefer TLS/SSH or a private network for the password in transit.

### Non-negotiables

- `SSO_REDIS_HOST`, `SSO_REDIS_PORT`, `SSO_REDIS_PASSWORD`, `SSO_REDIS_DB` are **identical in Holding and every subsidiary application**.
- `HOLDING_SSO_SECRET` is identical in Holding and every subsidiary that verifies signatures.
- Only the Holding compose stack runs the central `redis-sso` container; subsidiaries never start their own copy of the bus.

## Holding Configuration

```env
HOLDING_SSO_SECRET=
SSO_REDIS_HOST=127.0.0.1
SSO_REDIS_PORT=6380
SSO_REDIS_PASSWORD=
SSO_REDIS_DB=0
SSO_CACHE_REDIS_CONNECTION=sso
```

For a development-only setup, `HOLDING_SSO_SECRET` may stay empty. In production, set the same non-empty secret in Holding and every subsidiary application.

`tenant_applications.instance_url` must be a URL the **user's browser** can reach. On a shared server, `http://127.0.0.1:8091` works for local smoke tests; for real users use the deployed hostname (for example `https://akubumdes.example.test`), because the redirect happens in the browser and never inside a container network.

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
        $payload = Cache::store('sso')->pull($cacheKey);

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

The subsidiary must register the **same** `sso` cache store and `sso` redis connection shown under "Shared SSO Cache" (identical `SSO_REDIS_*` values, empty prefix) — reading from its default store is the bug that makes "Buka Aplikasi" always fail.

Add the route outside the authenticated middleware:

```php
Route::get('/auth/holding', [HoldingSsoController::class, 'store'])
    ->middleware(['web', 'throttle:10,1'])
    ->name('auth.holding');
```

Set the same configuration:

```php
// config/services.php
'holding_sso' => [
    'secret' => env('HOLDING_SSO_SECRET'),
],
```

```php
// config/cache.php  -> stores.sso   (driver redis, connection env SSO_CACHE_REDIS_CONNECTION, prefix '')
// config/database.php -> redis.sso  (host/port/password/database from SSO_REDIS_*)
// .env               -> identical SSO_REDIS_* values as Holding + HOLDING_SSO_SECRET
```

## Security Rules

- Cache entries live only in the dedicated `sso` store; the application default store must never hold a token.
- Cache entries are consumed with `Cache::store('sso')->pull`; a token can only be used once.
- Token lifetime is 60 seconds.
- Expired payloads must never create a session.
- Signature verification happens before cache consumption.
- On any failure, redirect to the subsidiary login page with a generic flash error.
- The user role in the payload is authoritative for the subsidiary, while tenant ownership is enforced by the application instance itself.
