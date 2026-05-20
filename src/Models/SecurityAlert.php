<?php

namespace Osit\LogSentinel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAlert extends Model
{
    protected $table = 'log_sentinel_alerts';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'meta' => 'array',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(LogEntry::class, 'entry_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LogSource::class, 'source_id');
    }
}
