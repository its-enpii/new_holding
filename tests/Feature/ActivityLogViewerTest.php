<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ActivityLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_and_filter_activity_logs(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant = Tenant::factory()->create();

        ActivityLog::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $superadmin->id,
            'action' => 'assign_app',
            'subject_type' => 'App\\Models\\TenantApplication',
            'subject_id' => 1,
            'metadata' => ['test' => 'data'],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ActivityLogs/Index')
                ->has('logs.data')
                ->has('distinctActions'));

        $this->actingAs($superadmin)
            ->get(route('admin.activity-logs.index', ['action' => 'assign_app']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ActivityLogs/Index')
                ->where('filters.action', 'assign_app'));
    }

    public function test_non_superadmin_cannot_view_activity_logs(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenant)->create();
        $staff = User::factory()->tenantStaff()->for($tenant)->create();

        $this->actingAs($owner)->get(route('admin.activity-logs.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.activity-logs.index'))->assertForbidden();
    }
}
