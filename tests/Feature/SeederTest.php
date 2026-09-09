<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TenantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_superadmin_and_demo_data(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@holding.local')->firstOrFail();
        $this->assertTrue($admin->isSuperadmin());
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertDatabaseHas('tenants', ['slug' => 'bumdesma-contoh']);
        $this->assertDatabaseHas('applications', ['slug' => 'sistem-contoh']);
        $this->assertDatabaseHas('users', ['email' => 'staff@tenant.test']);
        $this->assertSame(1, TenantApplication::query()->count());
    }
}
