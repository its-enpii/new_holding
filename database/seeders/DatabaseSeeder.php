<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Tenant;
use App\Models\TenantApplication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            'domain' => 'contoh.bumdesma.test',
            'email' => 'kontak@bumdesma.test',
            'phone' => '+62 812-0000-0000',
            'address' => 'Jl. Desa Contoh No. 1',
            'logo_path' => null,
            'is_active' => true,
        ]);

        $app = Application::query()->create([
            'name' => 'Sistem Contoh',
            'slug' => 'sistem-contoh',
            'description' => 'Aplikasi contoh registry Phase 1 & 2.',
            'icon_path' => 'widgets',
            'base_url' => 'https://aplikasi.example.test',
            'has_financial_report' => true,
            'is_active' => true,
        ]);

        $app2 = Application::query()->create([
            'name' => 'Sistem Simpan Pinjam',
            'slug' => 'simpan-pinjam',
            'description' => 'Aplikasi pengelolaan simpan pinjam desa.',
            'icon_path' => 'account_balance_wallet',
            'base_url' => 'https://sp.bumdesma.test',
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

        $tenant->users()->create([
            'name' => 'Staf Tenant',
            'email' => 'staff@tenant.test',
            'role' => 'tenant_staff',
            'password' => 'password',
            'is_active' => true,
        ]);

        TenantApplication::query()->create([
            'tenant_id' => $tenant->id,
            'application_id' => $app->id,
            'label' => 'Instance Utama',
            'instance_url' => 'https://aplikasi.example.test/instance-1',
            'api_secret' => Str::random(40),
            'is_active' => true,
            'activated_at' => now(),
            'expired_at' => now()->addYear(),
            'notes' => 'Lisensi produksi',
        ]);
    }
}
