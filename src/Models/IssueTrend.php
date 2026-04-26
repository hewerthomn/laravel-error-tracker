<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueTrend extends Model
{
    use UsesErrorTrackerConnection;

    protected $table = 'error_tracker_issue_trends';

    protected $guarded = [];

    protected $casts = [
        'bucket_start' => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }
}
