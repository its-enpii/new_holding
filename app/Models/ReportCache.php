<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReportCacheFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReportCache extends Model
{
    /** @use HasFactory<ReportCacheFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_application_id',
        'report_type',
        'period',
        'payload',
        'fetched_at',
        'expires_at',
    ];

    public function casts(): array
    {
        return [
            'payload' => 'array',
            'fetched_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function tenantApplication(): BelongsTo
    {
        return $this->belongsTo(TenantApplication::class);
    }

    /**
     * @param  Builder<ReportCache>  $query
     * @return Builder<ReportCache>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
