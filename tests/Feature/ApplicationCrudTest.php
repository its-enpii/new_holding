<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApplicationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_create_update_toggle_and_delete_application(): void
    {
        $user = User::factory()->superadmin()->create();

        $this->actingAs($user)->post(route('admin.applications.store'), [
            'name' => 'Kas Desa',
            'slug' => 'kas-desa',
            'description' => 'Aplikasi kas',
            'icon_path' => null,
            'base_url' => 'https://kas.example.test',
            'has_financial_report' => true,
            'is_active' => true,
        ])->assertRedirect(route('admin.applications.index'));

        $application = Application::query()->where('slug', 'kas-desa')->firstOrFail();

        $this->actingAs($user)->put(route('admin.applications.update', $application), [
            'name' => 'Kas Desa Modern',
            'slug' => 'kas-desa-modern',
            'description' => null,
            'icon_path' => null,
            'base_url' => 'https://kas-modern.example.test',
            'has_financial_report' => false,
            'is_active' => false,
        ])->assertRedirect(route('admin.applications.index'));

        $this->actingAs($user)->patch(route('admin.applications.toggle', $application))->assertRedirect();
        $this->assertTrue($application->fresh()->is_active);

        $this->actingAs($user)->delete(route('admin.applications.destroy', $application))->assertRedirect(route('admin.applications.index'));
        $this->assertModelMissing($application);
        $this->assertSame(4, ActivityLog::query()->where('subject_type', Application::class)->count());
    }

    public function test_application_create_validation_rejects_invalid_url(): void
    {
        $user = User::factory()->superadmin()->create();

        $this->actingAs($user)->post(route('admin.applications.store'), [
            'name' => 'Invalid URL',
            'slug' => 'invalid-url',
            'base_url' => 'not-a-url',
            'has_financial_report' => true,
            'is_active' => true,
        ])->assertSessionHasErrors('base_url');
    }
}
