<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperadminSeeder::class,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'BUMDesma Contoh',
            'slug' => 'bumdesma-contoh',
            'email' => 'kontak@bumdesma.test',
            'phone' => '+62 812-0000-0000',
            'address' => 'Jl. Desa Contoh No. 1',
            'logo_path' => null,
            'is_active' => true,
        ]);

        Application::query()->create([
            'name' => 'Sistem Contoh',
            'slug' => 'sistem-contoh',
            'description' => 'Aplikasi contoh registry Phase 1.',
            'icon_path' => null,
            'base_url' => 'https://aplikasi.example.test',
            'has_financial_report' => true,
            'is_active' => true,
        ]);

        $tenant->users()->create([
            'name' => 'Owner Tenant',
            'email' => 'owner@tenant.test',
            'role' => 'tenant_owner',
            'password' => 'password',
            'is_active' => true,
        ]);
    }
}
