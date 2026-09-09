<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class BrandedErrorPageTest extends TestCase
{
    public function test_missing_page_renders_branded_inertia_error(): void
    {
        $this->followingRedirects()
            ->get('/halaman-tidak-ada')
            ->assertStatus(404)
            ->assertInertia(fn ($page) => $page->component('Errors/404')->has('status'));
    }
}
