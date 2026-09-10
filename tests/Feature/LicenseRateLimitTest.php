<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class LicenseRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_rate_limited_at_sixty_requests_per_minute(): void
    {
        $user = User::factory()->superadmin()->create();
        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }

        $this->actingAs($user)->get(route('dashboard'))->assertStatus(429);
    }

    public function test_logout_is_not_rate_limited_after_web_app_limit_is_reached(): void
    {
        $user = User::factory()->superadmin()->create();
        for ($attempt = 1; $attempt <= 61; $attempt++) {
            $this->actingAs($user)->get(route('dashboard'));
        }

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_quick_access_is_rate_limited_at_ten_requests_per_minute(): void
    {
        $user = User::factory()->tenantOwner()->for(Tenant::factory()->create())->create();
        $license = TenantApplication::factory()
            ->for($user->tenant, 'tenant')
            ->for(Application::factory(), 'application')
            ->create([
                'instance_url' => 'https://pos.desa.test/app',
                'is_active' => true,
                'expired_at' => now()->addMonth(),
            ]);

        $this->hitNamedLimiter('app.access', 10, $user->id, 59);

        for ($attempt = 1; $attempt <= 9; $attempt++) {
            $this->actingAs($user)->post(route('app.access', $license))->assertRedirect();
        }

        $this->actingAs($user)->post(route('app.access', $license))->assertStatus(429);
    }

    public function test_rate_limit_response_renders_branded_page(): void
    {
        $user = User::factory()->superadmin()->create();
        for ($attempt = 1; $attempt <= 61; $attempt++) {
            $this->actingAs($user)->get(route('dashboard'));
        }

        $this->actingAs($user)->get(route('dashboard'))
            ->assertStatus(429)
            ->assertInertia(fn ($page) => $page
                ->component('Errors/429')
                ->where('status', 429));
    }

    private function hitNamedLimiter(string $name, int $limit, int $userId, int $hits): string
    {
        RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute($limit)->by((string) $userId));
        RateLimiter::hit(md5($name.$userId), $hits);

        return md5($name.$userId);
    }
}
