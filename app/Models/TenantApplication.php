<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TenantApplication extends Model
{
    /** @use HasFactory<TenantApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'application_id',
        'label',
        'instance_url',
        'sub_tenant_code',
        'api_secret',
        'is_active',
        'activated_at',
        'expired_at',
        'notes',
        'connection_status',
        'connection_latency_ms',
        'connection_checked_at',
    ];

    public static function boot(): void
    {
        parent::boot();

        self::creating(function (TenantApplication $model): void {
            if (empty($model->api_secret)) {
                $model->api_secret = Str::random(40);
            }
            if ($model->activated_at === null && $model->is_active) {
                $model->activated_at = now();
            }
        });
    }

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
            'expired_at' => 'datetime',
            'connection_checked_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    /**
     * @param  Builder<TenantApplication>  $query
     * @return Builder<TenantApplication>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $scope) => $scope
                ->whereNull('expired_at')
                ->orWhere('expired_at', '>', now()));
    }
}
