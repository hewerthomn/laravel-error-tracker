<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    use UsesErrorTrackerConnection;

    protected $table = 'error_tracker_issues';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'muted_until' => 'datetime',
        'resolved_at' => 'datetime',
        'ignored_at' => 'datetime',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'issue_id');
    }

    public function trends(): HasMany
    {
        return $this->hasMany(IssueTrend::class, 'issue_id');
    }

    public function lastEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'last_event_id');
    }
}
