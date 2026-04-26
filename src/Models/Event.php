<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    use UsesErrorTrackerConnection;

    protected $table = 'error_tracker_events';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'trace_json' => 'array',
        'context_json' => 'array',
        'headers_json' => 'array',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class, 'event_id');
    }
}
