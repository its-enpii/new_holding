<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReportCache;
use App\Models\TenantApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCache>
 */
final class ReportCacheFactory extends Factory
{
    protected $model = ReportCache::class;

    public function definition(): array
    {
        return [
            'tenant_application_id' => TenantApplication::factory(),
            'report_type' => 'balance_sheet',
            'period' => now()->format('Y-m'),
            'payload' => ['status' => 'success', 'data' => []],
            'fetched_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'fetched_at' => now()->subHour(),
            'expires_at' => now()->subMinutes(30),
        ]);
    }
}
