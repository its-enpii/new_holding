<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'email',
        'phone',
        'address',
        'logo_path',
        'is_active',
    ];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tenantApplications(): HasMany
    {
        return $this->hasMany(TenantApplication::class);
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'tenant_applications')
            ->withPivot(['id', 'label', 'instance_url', 'api_secret', 'is_active', 'activated_at', 'expired_at', 'notes'])
            ->withTimestamps();
    }
}
