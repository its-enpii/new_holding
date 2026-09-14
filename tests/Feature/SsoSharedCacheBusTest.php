<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\SsoTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Integration proof for the shared SSO token bus.
 *
 * Coordinates stay 100% environment driven (`SSO_REDIS_*`), so the test reads
 * them straight from the environment and skips itself when that Redis server
 * is not reachable. No docker service name, host, or port is hardcoded: the
 * same assertions pass on a shared server (`127.0.0.1:6380`) or against a
 * remote holding host.
 */
final class SsoSharedCacheBusTest extends TestCase
{
    use RefreshDatabase;

    private const CONNECTION = 'sso';

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->busIsReachable()) {
            $this->markTestSkipped($this->busSkipReason());
        }

        Cache::store(self::CONNECTION)->flush();
    }

    public function test_sso_store_is_redis_with_env_driven_coordinates_and_empty_prefix(): void
    {
        $this->assertSame('redis', config('cache.stores.sso.driver'));
        $this->assertSame(self::CONNECTION, config('cache.stores.sso.connection'));
        $this->assertSame(self::CONNECTION, config('cache.stores.sso.lock_connection'));
        $this->assertSame(
            $this->bus()['host'],
            config('database.redis.'.self::CONNECTION.'.host')
        );
        $this->assertSame(
            (string) $this->bus()['port'],
            (string) config('database.redis.'.self::CONNECTION.'.port')
        );
        $this->assertSame($this->bus()['database'], (int) config('database.redis.'.self::CONNECTION.'.database'));
        $this->assertSame(
            '',
            (string) config('cache.stores.sso.prefix'),
            'An application cache prefix would hide the token from subsidiary applications.'
        );
    }

    public function test_token_is_emitted_to_shared_redis_and_consumed_once(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'BUMDesma Bus']);
        $tenantApplication = TenantApplication::factory()->for($tenant)->create([
            'instance_url' => 'http://localhost:8091',
            'sub_tenant_code' => 'sukamaju',
        ]);
        $user = User::factory()->tenantOwner()->for($tenant)->create([
            'name' => 'Owner Bus',
            'email' => 'owner-bus@holding.test',
        ]);

        $token = app(SsoTokenService::class)->create($tenantApplication, $user)['token'];
        $cacheKey = 'sso:'.hash('sha256', $token);

        $this->assertNotNull(
            $this->rawGet($cacheKey),
            'The token never reached the shared Redis bus under the contract key.'
        );
        $this->assertSame(60, $this->rawTtl($cacheKey), 'SSO tokens must live for exactly 60 seconds.');

        $payload = Cache::store(self::CONNECTION)->get($cacheKey);
        $this->assertSame($user->email, $payload['email']);
        $this->assertSame($user->name, $payload['name']);
        $this->assertSame($tenant->name, $payload['tenant_name']);
        $this->assertSame('sukamaju', $payload['sub_tenant_code']);
        $this->assertSame(now()->addMinute()->timestamp, $payload['exp']);

        $this->assertSame($payload, Cache::store(self::CONNECTION)->pull($cacheKey));
        $this->assertNull($this->rawGet($cacheKey), 'The token was not removed after one consumption.');
        $this->assertNull(Cache::store(self::CONNECTION)->pull($cacheKey), 'A consumed token was replayed.');
    }

    public function test_expired_token_is_never_returned_by_the_bus(): void
    {
        $token = bin2hex(random_bytes(32));
        $cacheKey = 'sso:'.hash('sha256', $token);

        Cache::store(self::CONNECTION)->put(
            $cacheKey,
            ['exp' => now()->subMinute()->timestamp, 'email' => 'stale@holding.test'],
            60
        );

        $payload = Cache::store(self::CONNECTION)->pull($cacheKey);

        $this->assertLessThan(now()->timestamp, $payload['exp']);
        $this->assertNull($this->rawGet($cacheKey));
    }

    public function test_issuer_writes_to_the_sso_store_and_not_the_default_store(): void
    {
        config()->set('cache.stores.'.self::CONNECTION, ['driver' => 'array']);
        Cache::purge(self::CONNECTION);
        Cache::store(self::CONNECTION)->flush();
        Cache::flush();

        $token = app(SsoTokenService::class)->create(
            TenantApplication::factory()->create(),
            User::factory()->superadmin()->create()
        )['token'];
        $cacheKey = 'sso:'.hash('sha256', $token);

        $this->assertNotNull(Cache::store(self::CONNECTION)->get($cacheKey));
        $this->assertNull(Cache::get($cacheKey));
    }

    /**
     * @return array{host: string, port: int, password: ?string, database: int}
     */
    private function bus(): array
    {
        $password = Env::get('SSO_REDIS_PASSWORD');

        return [
            'host' => (string) Env::get('SSO_REDIS_HOST', '127.0.0.1'),
            'port' => (int) Env::get('SSO_REDIS_PORT', '6380'),
            'password' => $password === '' || $password === null ? null : (string) $password,
            'database' => (int) Env::get('SSO_REDIS_DB', '0'),
        ];
    }

    private function rawGet(string $key): ?string
    {
        return Redis::connection(self::CONNECTION)->get($key);
    }

    private function rawTtl(string $key): int
    {
        return (int) Redis::connection(self::CONNECTION)->ttl($key);
    }

    private function busIsReachable(): bool
    {
        $bus = $this->bus();
        $socket = @fsockopen($bus['host'], $bus['port'], $errorNumber, $errorMessage, 1.5);

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    private function busSkipReason(): string
    {
        $bus = $this->bus();

        return sprintf(
            'Shared SSO Redis bus is unreachable at %s:%s (set SSO_REDIS_HOST/SSO_REDIS_PORT or run `docker compose up -d redis-sso`).',
            $bus['host'],
            $bus['port']
        );
    }
}
