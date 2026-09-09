<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
