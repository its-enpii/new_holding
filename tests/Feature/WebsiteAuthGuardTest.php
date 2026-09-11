<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebsiteAuthGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_accessing_website_admin_routes(): void
    {
        $this->get(route('website.posts.index'))->assertRedirect(route('login'));
        $this->get(route('website.pages.index'))->assertRedirect(route('login'));
        $this->get(route('website.settings.edit'))->assertRedirect(route('login'));
        $this->get(route('website.messages.index'))->assertRedirect(route('login'));
    }

    public function test_non_superadmin_user_is_forbidden_from_website_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->tenantOwner($tenant)->create();
        $staff = User::factory()->tenantStaff($tenant)->create();

        // Tenant owner -> 403
        $this->actingAs($owner)->get(route('website.posts.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('website.pages.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('website.settings.edit'))->assertForbidden();
        $this->actingAs($owner)->get(route('website.messages.index'))->assertForbidden();

        // Tenant staff -> 403
        $this->actingAs($staff)->get(route('website.posts.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('website.pages.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('website.settings.edit'))->assertForbidden();
        $this->actingAs($staff)->get(route('website.messages.index'))->assertForbidden();
    }
}
