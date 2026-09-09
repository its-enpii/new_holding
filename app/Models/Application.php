<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_path',
        'base_url',
        'has_financial_report',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'has_financial_report' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenantApplications(): HasMany
    {
        return $this->hasMany(TenantApplication::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_applications')
            ->withPivot(['id', 'label', 'instance_url', 'api_secret', 'is_active', 'activated_at', 'expired_at', 'notes'])
            ->withTimestamps();
    }
}
