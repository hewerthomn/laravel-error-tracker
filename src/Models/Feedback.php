<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use UsesErrorTrackerConnection;

    protected $table = 'error_tracker_feedback';

    protected $guarded = [];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
