<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_page(): void
    {
        $this->get(route('login'))->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->superadmin()->create();

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->superadmin()->inactive()->create();

        $this->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->superadmin()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(429);
    }
}
