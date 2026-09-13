<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Tests\TestCase;

final class PwaAndErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_served_with_pwa_metadata(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Holding Portal - Sistem Manajemen Usaha Terpadu', $manifest['name']);
        $this->assertSame('#4338ca', $manifest['theme_color']);
        $this->assertCount(2, $manifest['icons']);
    }

    public function test_service_worker_and_offline_shell_are_available(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $this->assertStringContainsString('/offline.html', (string) file_get_contents($swPath));

        $offlinePath = public_path('offline.html');
        $this->assertFileExists($offlinePath);
        $offlineContent = (string) file_get_contents($offlinePath);
        $this->assertStringContainsString('Koneksi sedang terputus', $offlineContent);
        $this->assertStringContainsString('Akses data lokal tetap berjalan', $offlineContent);
        $this->assertStringContainsString('Coba Lagi', $offlineContent);
    }

    public function test_web_errors_render_branded_inertia_components(): void
    {
        Route::get('/_test-error/{code}', function (int $code) {
            abort($code);
        });

        foreach ([401, 403, 404, 419, 429, 500, 503] as $statusCode) {
            $this->get("/_test-error/{$statusCode}")
                ->assertStatus($statusCode)
                ->assertInertia(fn ($page) => $page->component("Errors/{$statusCode}")->has('status'));
        }
    }

    public function test_blade_error_views_contain_branded_elements(): void
    {
        foreach ([401, 403, 404, 419, 429, 500, 503] as $code) {
            $html = view("errors.{$code}")->render();
            $this->assertStringContainsString('Holding Portal', $html);
            $this->assertStringContainsString((string) $code, $html);
        }
    }

    public function test_non_inertia_errors_fall_back_to_branded_blade_views(): void
    {
        Route::get('/_test-blade-error/{code}', function (int $code) {
            abort($code);
        });

        foreach ([401, 403, 404, 419, 429, 500, 503] as $statusCode) {
            $this->get("/_test-blade-error/{$statusCode}", ['X-Inertia' => 'false'])
                ->assertStatus($statusCode)
                ->assertSee('Holding Portal', false);
        }
    }

    public function test_inertia_render_failure_falls_back_to_branded_blade(): void
    {
        Inertia::setRootView('_missing-error-root-view');

        Route::get('/_test-render-fail', function () {
            abort(404);
        });

        $this->get('/_test-render-fail')
            ->assertStatus(404)
            ->assertSee('Halaman Tidak Ditemukan', false)
            ->assertSee('Holding Portal', false);
    }
}
