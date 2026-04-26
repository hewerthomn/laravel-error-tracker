<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $fingerprint
 * @property string $title
 * @property string $level
 * @property string $status
 * @property string $environment
 * @property string|null $exception_class
 * @property string|null $message_sample
 * @property Carbon|null $first_seen_at
 * @property Carbon|null $last_seen_at
 * @property int $total_events
 * @property int $affected_users
 * @property int|null $last_event_id
 * @property Carbon|null $muted_until
 * @property string|null $mute_reason
 * @property Carbon|null $resolved_at
 * @property Carbon|null $ignored_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event|null $lastEvent
 * @property-read Collection<int, Event> $events
 * @property-read Collection<int, IssueTrend> $trends
 */
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

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'issue_id');
    }

    /**
     * @return HasMany<IssueTrend, $this>
     */
    public function trends(): HasMany
    {
        return $this->hasMany(IssueTrend::class, 'issue_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function lastEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'last_event_id');
    }
}
