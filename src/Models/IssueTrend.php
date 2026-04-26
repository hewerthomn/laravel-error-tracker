<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $issue_id
 * @property Carbon|null $bucket_start
 * @property string $bucket_granularity
 * @property int $events_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Issue $issue
 */
class IssueTrend extends Model
{
    use UsesErrorTrackerConnection;

    protected $table = 'error_tracker_issue_trends';

    protected $guarded = [];

    protected $casts = [
        'bucket_start' => 'datetime',
    ];

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }
}
