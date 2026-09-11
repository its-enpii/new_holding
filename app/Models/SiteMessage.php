<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SiteMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SiteMessage extends Model
{
    /** @use HasFactory<SiteMessageFactory> */
    use HasFactory;

    protected $table = 'site_messages';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function isRead(): bool
    {
        return $this->getAttribute('read_at') !== null;
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
