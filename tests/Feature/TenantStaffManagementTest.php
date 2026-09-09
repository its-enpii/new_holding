<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class TenantStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_owner_can_list_create_update_toggle_and_delete_staff(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenant)->create();

        // List staff
        $this->actingAs($owner)
            ->get(route('tenant.staff.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Staff/Index')
                ->has('staff.data'));

        // Create staff
        $this->actingAs($owner)->post(route('tenant.staff.store'), [
            'name' => 'Budi Staf',
            'email' => 'budi@tenant.test',
            'role' => User::ROLE_TENANT_STAFF,
            'password' => 'secret123',
            'is_active' => true,
        ])->assertRedirect(route('tenant.staff.index'));

        $staff = User::query()->where('email', 'budi@tenant.test')->firstOrFail();
        $this->assertSame($tenant->id, $staff->tenant_id);
        $this->assertTrue(Hash::check('secret123', $staff->password));

        // Update staff
        $this->actingAs($owner)->put(route('tenant.staff.update', $staff), [
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@tenant.test',
            'role' => User::ROLE_TENANT_OWNER,
            'is_active' => true,
        ])->assertRedirect(route('tenant.staff.index'));

        $staff->refresh();
        $this->assertSame('Budi Santoso', $staff->name);
        $this->assertSame(User::ROLE_TENANT_OWNER, $staff->role);

        // Toggle staff
        $this->actingAs($owner)->patch(route('tenant.staff.toggle', $staff))->assertRedirect();
        $this->assertFalse($staff->fresh()->is_active);

        // Delete staff
        $this->actingAs($owner)->delete(route('tenant.staff.destroy', $staff))->assertRedirect(route('tenant.staff.index'));
        $this->assertModelMissing($staff);

        $this->assertGreaterThanOrEqual(3, ActivityLog::query()->where('subject_type', User::class)->count());
    }

    public function test_tenant_owner_can_reset_staff_password(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenant)->create();
        $staff = User::factory()->tenantStaff()->for($tenant)->create([
            'password' => 'oldpassword',
        ]);

        $this->actingAs($owner)->post(route('tenant.staff.reset-password', $staff), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $staff->refresh();
        $this->assertTrue(Hash::check('newpassword123', $staff->password));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'staff_password_reset',
            'subject_type' => User::class,
            'subject_id' => $staff->id,
        ]);
    }

    public function test_tenant_owner_cannot_demote_or_deactivate_self(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner()->for($tenant)->create();

        // Attempt self-demotion
        $this->actingAs($owner)->put(route('tenant.staff.update', $owner), [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => User::ROLE_TENANT_STAFF,
            'is_active' => true,
        ])->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_TENANT_OWNER, $owner->fresh()->role);

        // Attempt self-deactivation via update
        $this->actingAs($owner)->put(route('tenant.staff.update', $owner), [
            'name' => $owner->name,
            'email' => $owner->email,
            'role' => User::ROLE_TENANT_OWNER,
            'is_active' => false,
        ])->assertSessionHasErrors('is_active');

        // Attempt self-deactivation via toggle
        $this->actingAs($owner)->patch(route('tenant.staff.toggle', $owner))
            ->assertSessionHasErrors('error');

        // Attempt self-deletion
        $this->actingAs($owner)->delete(route('tenant.staff.destroy', $owner))
            ->assertSessionHasErrors('error');

        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_tenant_owner_cannot_manage_staff_from_other_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $ownerA = User::factory()->tenantOwner()->for($tenantA)->create();
        $staffB = User::factory()->tenantStaff()->for($tenantB)->create();

        $this->actingAs($ownerA)->get(route('tenant.staff.edit', $staffB))->assertForbidden();
        $this->actingAs($ownerA)->put(route('tenant.staff.update', $staffB), [
            'name' => 'Hacked',
            'email' => $staffB->email,
            'role' => $staffB->role,
            'is_active' => true,
        ])->assertForbidden();
        $this->actingAs($ownerA)->delete(route('tenant.staff.destroy', $staffB))->assertForbidden();
    }

    public function test_tenant_staff_is_forbidden_from_staff_management(): void
    {
        $tenant = Tenant::factory()->create();
        $staff = User::factory()->tenantStaff()->for($tenant)->create();

        $this->actingAs($staff)->get(route('tenant.staff.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('tenant.staff.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('tenant.staff.store'), [
            'name' => 'Unauthorized',
            'email' => 'unauth@test.test',
            'role' => User::ROLE_TENANT_STAFF,
            'password' => 'secret123',
        ])->assertForbidden();
    }
}
