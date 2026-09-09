<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_update_toggle_and_delete_tenant(): void
    {
        $user = User::factory()->superadmin()->create();

        $response = $this->actingAs($user)->post(route('admin.tenants.store'), [
            'name' => 'BUMDesma Maju',
            'slug' => 'bumdesma-maju',
            'email' => 'maju@holding.test',
            'phone' => '+62 812-1111-1111',
            'address' => 'Jl. Maju No. 1',
            'logo_path' => null,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.tenants.index'));
        $tenant = Tenant::query()->where('slug', 'bumdesma-maju')->firstOrFail();

        $this->actingAs($user)->put(route('admin.tenants.update', $tenant), [
            'name' => 'BUMDesma Maju Bersama',
            'slug' => 'bumdesma-maju-bersama',
            'email' => 'maju@holding.test',
            'phone' => null,
            'address' => null,
            'logo_path' => null,
            'is_active' => false,
        ])->assertRedirect(route('admin.tenants.index'));

        $tenant->refresh();
        $this->assertFalse($tenant->is_active);

        $this->actingAs($user)->patch(route('admin.tenants.toggle', $tenant))->assertRedirect();
        $this->assertTrue($tenant->fresh()->is_active);

        $this->actingAs($user)->delete(route('admin.tenants.destroy', $tenant))->assertRedirect(route('admin.tenants.index'));
        $this->assertModelMissing($tenant);
        $this->assertSame(4, ActivityLog::query()->where('subject_type', Tenant::class)->count());
    }

    public function test_tenant_create_validation_rejects_duplicate_slug(): void
    {
        $user = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($user)->post(route('admin.tenants.store'), [
            'name' => 'Duplicate',
            'slug' => $tenant->slug,
            'email' => 'duplicate@holding.test',
            'is_active' => true,
        ])->assertSessionHasErrors('slug');
    }
}
