<?php

namespace Osit\LogSentinel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogSource extends Model
{
    protected $table = 'log_sentinel_sources';

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'last_scanned_at' => 'datetime',
        'meta' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(LogEntry::class, 'source_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class, 'source_id');
    }
}
