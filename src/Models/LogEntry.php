<?php

namespace Osit\LogSentinel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogEntry extends Model
{
    protected $table = 'log_sentinel_entries';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(LogSource::class, 'source_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class, 'entry_id');
    }
}
