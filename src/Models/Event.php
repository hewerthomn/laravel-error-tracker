<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $issue_id
 * @property string $uuid
 * @property Carbon|null $occurred_at
 * @property string $level
 * @property string|null $exception_class
 * @property string|null $message
 * @property string|null $file
 * @property int|null $line
 * @property string|null $request_method
 * @property string|null $request_path
 * @property string|null $route_name
 * @property string|null $url
 * @property int|null $status_code
 * @property string|null $command_name
 * @property string|null $job_name
 * @property string $environment
 * @property string|null $release
 * @property string|null $user_id
 * @property string|null $user_type
 * @property string|null $user_label
 * @property string|null $ip_hash
 * @property array<int, mixed>|array<string, mixed>|null $trace_json
 * @property array<string, mixed>|null $context_json
 * @property array<string, mixed>|null $headers_json
 * @property string|null $feedback_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Issue $issue
 * @property-read Feedback|null $feedback
 */
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

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    /**
     * @return HasOne<Feedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class, 'event_id');
    }
}
