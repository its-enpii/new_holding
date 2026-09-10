<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Services\AppConnectionCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AppConnectionCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_maps_successful_ping_to_connected(): void
    {
        $tenantApplication = TenantApplication::factory()->for(Application::factory(), 'application')->create([
            'instance_url' => 'https://connected.test/app/',
        ]);
        $service = app(AppConnectionCheckService::class);

        Http::fake(['https://connected.test/app/api/v1/holding/ping' => Http::response(['status' => 'success'], 200)]);

        $result = $service->check($tenantApplication);

        $this->assertSame('connected', $result['status']);
        $this->assertSame('Koneksi berhasil.', $result['message']);
        $this->assertGreaterThanOrEqual(0, $result['latency_ms']);
        $this->assertArrayHasKey('checked_at', $result);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://connected.test/app/api/v1/holding/ping'
            && $request->hasHeader('Authorization', 'Bearer '.$tenantApplication->api_secret));
    }

    public function test_service_maps_unauthorized_ping_to_auth_error(): void
    {
        $tenantApplication = TenantApplication::factory()->for(Application::factory(), 'application')->create([
            'instance_url' => 'https://auth.test',
        ]);

        Http::fake(['https://auth.test/api/v1/holding/ping' => Http::response(['message' => 'invalid token'], 401)]);

        $result = app(AppConnectionCheckService::class)->check($tenantApplication);

        $this->assertSame('auth_error', $result['status']);
        $this->assertSame('Secret atau URL instance salah.', $result['message']);
    }

    public function test_service_maps_connection_exception_to_offline(): void
    {
        $tenantApplication = TenantApplication::factory()->for(Application::factory(), 'application')->create([
            'instance_url' => 'https://offline.test',
        ]);

        Http::fake(fn (): never => throw new ConnectionException('Could not resolve host'));

        $result = app(AppConnectionCheckService::class)->check($tenantApplication);

        $this->assertSame('offline', $result['status']);
        $this->assertSame('Instance tidak dapat dihubungi.', $result['message']);
    }

    public function test_service_maps_server_error_to_offline(): void
    {
        $tenantApplication = TenantApplication::factory()->for(Application::factory(), 'application')->create([
            'instance_url' => 'https://error.test',
        ]);

        Http::fake(['https://error.test/api/v1/holding/ping' => Http::response('server error', 500)]);

        $result = app(AppConnectionCheckService::class)->check($tenantApplication);

        $this->assertSame('offline', $result['status']);
        Http::assertSentCount(1);
    }

    public function test_superadmin_can_test_connection_and_result_is_persisted(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $tenantApplication = TenantApplication::factory()->for($tenant, 'tenant')->for(Application::factory(), 'application')->create([
            'instance_url' => 'https://connected.test/app',
        ]);

        Http::fake(['https://connected.test/app/api/v1/holding/ping' => Http::response(['status' => 'success'], 200)]);

        $response = $this->actingAs($superadmin)
            ->postJson(route('admin.tenants.applications.test-connection', [$tenant, $tenantApplication]));

        $response->assertOk()
            ->assertJson(['status' => 'connected', 'message' => 'Koneksi berhasil.']);
        $tenantApplication->refresh();
        $this->assertSame('connected', $tenantApplication->connection_status);
        $this->assertNotNull($tenantApplication->connection_checked_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'test_connection',
            'subject_type' => TenantApplication::class,
            'subject_id' => $tenantApplication->id,
        ]);
    }

    public function test_non_superadmin_cannot_test_connection(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenant)->create();
        $tenantApplication = TenantApplication::factory()->for($tenant, 'tenant')->for(Application::factory(), 'application')->create([
            'instance_url' => 'https://connected.test/app',
        ]);

        $this->actingAs($owner)
            ->postJson(route('admin.tenants.applications.test-connection', [$tenant, $tenantApplication]))
            ->assertForbidden();

        $this->assertNull($tenantApplication->fresh()->connection_status);
        Http::assertNothingSent();
    }

    public function test_connection_endpoint_is_limited_to_six_requests_per_minute(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $tenantApplication = TenantApplication::factory()->for($tenant, 'tenant')->for(Application::factory(), 'application')->create([
            'instance_url' => 'https://connected.test/app',
        ]);

        Http::fake(['https://connected.test/api/v1/holding/ping' => Http::response(['status' => 'success'], 200)]);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->actingAs($superadmin)
                ->postJson(route('admin.tenants.applications.test-connection', [$tenant, $tenantApplication]))
                ->assertOk();
        }

        $this->actingAs($superadmin)
            ->postJson(route('admin.tenants.applications.test-connection', [$tenant, $tenantApplication]))
            ->assertStatus(429);
    }

    public function test_store_normalizes_instance_url(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();
        $application = Application::factory()->create();

        $this->actingAs($superadmin)
            ->post(route('admin.tenants.applications.store', $tenant), [
                'application_id' => $application->id,
                'instance_url' => '  https://sentral.tenant.test/app/  ',
            ])
            ->assertRedirect();

        $this->assertSame('https://sentral.tenant.test/app', TenantApplication::query()->latest('id')->first()->instance_url);
    }
}
