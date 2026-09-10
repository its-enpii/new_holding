<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\CheckLicenseExpiry;
use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use App\Notifications\LicenseExpired;
use App\Notifications\LicenseExpiringSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LicenseExpiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->superadmin()->create();
    }

    public function test_expiring_soon_licenses_notify_superadmins_once_per_day(): void
    {
        $license = $this->createLicense(['expired_at' => now()->addDays(3)]);

        $this->artisan('licenses:check-expiry')->assertSuccessful();
        $this->artisan('licenses:check-expiry')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->superadmin->id,
            'type' => LicenseExpiringSoon::class,
            'data->tenant_application_id' => $license->id,
            'data->tenant_id' => $license->tenant_id,
            'data->tenant_name' => $license->tenant->name,
            'data->application_name' => $license->application->name,
            'data->window_date' => now()->toDateString(),
        ]);
    }

    public function test_expired_licenses_notify_superadmins_only_once(): void
    {
        $license = $this->createLicense(['expired_at' => now()->subHour()]);

        $this->artisan('licenses:check-expiry')->assertSuccessful();
        $this->artisan('licenses:check-expiry')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->superadmin->id,
            'type' => LicenseExpired::class,
            'data->tenant_application_id' => $license->id,
        ]);
    }

    public function test_licenses_without_expiry_or_inactive_licenses_are_ignored(): void
    {
        $this->createLicense(['expired_at' => null]);
        $this->createLicense(['is_active' => false, 'expired_at' => now()->addDay()]);

        $this->artisan(CheckLicenseExpiry::class)->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_dashboard_includes_expiring_and_expired_license_alerts_for_superadmin(): void
    {
        $expiring = $this->createLicense(['expired_at' => now()->addDays(2)]);
        $expired = $this->createLicense(['expired_at' => now()->subDay()]);

        $this->actingAs($this->superadmin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('licenseAlerts.items', 2)
                ->where('licenseAlerts.total', 2)
                ->where('licenseAlerts.items.0.id', $expiring->id)
                ->where('licenseAlerts.items.0.status', 'expiring')
                ->where('licenseAlerts.items.1.id', $expired->id)
                ->where('licenseAlerts.items.1.status', 'expired'));
    }

    public function test_tenant_dashboard_exposes_expiring_soon_flag(): void
    {
        $license = $this->createLicense(['expired_at' => now()->addDays(2)]);
        $owner = User::factory()->tenantOwner()->for($license->tenant)->create();

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Dashboard')
                ->where('applications.0.id', $license->id)
                ->where('applications.0.is_expiring_soon', true));
    }

    private function createLicense(array $attributes): TenantApplication
    {
        return TenantApplication::factory()
            ->for(Tenant::factory(), 'tenant')
            ->for(Application::factory(), 'application')
            ->create($attributes);
    }
}
