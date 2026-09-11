<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SiteMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebsiteContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_submit_contact_message(): void
    {
        $this->get(route('public.contact'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('PublicSite/Contact'));

        $this->post(route('public.contact.store'), [
            'name' => 'Budi Pratama',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'subject' => 'Pertanyaan Integrasi Aplikasi',
            'message' => 'Halo, kami ingin menanyakan prosedur pendaftaran unit bisnis baru ke holding portal.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('site_messages', [
            'name' => 'Budi Pratama',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'subject' => 'Pertanyaan Integrasi Aplikasi',
        ]);
    }

    public function test_honeypot_field_silently_discards_bot_submissions(): void
    {
        $this->post(route('public.contact.store'), [
            'name' => 'Bot Spammer',
            'email' => 'spammer@bot.com',
            'message' => 'Buy cheap links now!',
            'website' => 'http://spam-link.test', // honeypot filled
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('site_messages', [
            'name' => 'Bot Spammer',
        ]);
    }

    public function test_superadmin_can_view_mark_read_and_delete_messages(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $message = SiteMessage::factory()->create(['name' => 'Siti Nurhaliza', 'read_at' => null]);

        $this->actingAs($superadmin)->get(route('website.messages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Website/Messages/Index')
                ->has('messages.data', 1)
                ->where('unreadCount', 1)
            );

        // Mark read
        $this->actingAs($superadmin)->post(route('website.messages.mark-read', $message))
            ->assertRedirect();

        $message->refresh();
        $this->assertTrue($message->isRead());

        // Delete message
        $this->actingAs($superadmin)->delete(route('website.messages.destroy', $message))
            ->assertRedirect(route('website.messages.index'));

        $this->assertModelMissing($message);
    }
}
