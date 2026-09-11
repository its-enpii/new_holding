<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\SsoTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SsoTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_one_time_payload_and_hashed_cache_entry(): void
    {
        config(['services.holding_sso.secret' => null]);
        Cache::flush();

        $tenant = Tenant::factory()->create(['name' => 'BUMDesma Contoh']);
        $tenantApplication = TenantApplication::factory()->for($tenant)->create([
            'instance_url' => 'https://sidbm.test',
        ]);
        $user = User::factory()->tenantOwner()->for($tenant)->create();
        $service = app(SsoTokenService::class);

        $first = $service->create($tenantApplication, $user);
        $second = $service->create($tenantApplication, $user);

        $this->assertSame(64, strlen($first['token']));
        $this->assertNotSame($first['token'], $second['token']);
        $this->assertSame(
            $first['payload'],
            Cache::get('sso:'.hash('sha256', $first['token']))
        );
        $this->assertSame($tenantApplication->id, $first['payload']['tenant_application_id']);
        $this->assertSame($user->id, $first['payload']['user_id']);
        $this->assertSame($user->email, $first['payload']['email']);
        $this->assertSame($user->name, $first['payload']['name']);
        $this->assertSame($user->role, $first['payload']['role']);
        $this->assertSame($tenant->name, $first['payload']['tenant_name']);
        $this->assertGreaterThan(now()->timestamp, $first['payload']['exp']);
        $this->assertLessThanOrEqual(now()->addMinute()->timestamp, $first['payload']['exp']);
        $this->assertArrayNotHasKey('signature', $first['payload']);

        $firstTokenCacheKey = 'sso:'.hash('sha256', $first['token']);
        Cache::forget($firstTokenCacheKey);

        $this->assertNull(Cache::get($firstTokenCacheKey));
    }

    public function test_adds_hmac_signature_when_secret_is_set(): void
    {
        config(['services.holding_sso.secret' => 'shared-secret']);
        Cache::flush();

        $tenantApplication = TenantApplication::factory()->create();
        $user = User::factory()->superadmin()->create();

        $result = app(SsoTokenService::class)->create($tenantApplication, $user);
        $payload = $result['payload'];
        $signature = $payload['signature'];
        unset($payload['signature']);

        $this->assertSame(
            hash_hmac('sha256', json_encode($payload), 'shared-secret'),
            $signature
        );
    }
}
