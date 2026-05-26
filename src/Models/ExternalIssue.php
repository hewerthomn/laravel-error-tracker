<?php

namespace Hewerthomn\ErrorTracker\Models;

use Hewerthomn\ErrorTracker\Models\Concerns\UsesErrorTrackerConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * @property int $id
 * @property int $issue_id
 * @property string $provider
 * @property string $repository
 * @property string|null $external_id
 * @property int $external_number
 * @property string $title
 * @property string $url
 * @property string|null $state
 * @property int|null $created_from_event_id
 * @property array<string, mixed>|null $metadata_json
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Issue $issue
 * @property-read Event|null $createdFromEvent
 */
class ExternalIssue extends Model
{
    use UsesErrorTrackerConnection;

    protected $table = 'error_tracker_external_issues';

    protected $fillable = [
        'issue_id',
        'provider',
        'repository',
        'external_id',
        'external_number',
        'title',
        'url',
        'state',
        'created_from_event_id',
        'metadata_json',
        'last_synced_at',
    ];

    protected $casts = [
        'external_number' => 'integer',
        'metadata_json' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function createdFromEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'created_from_event_id');
    }

    public static function tableExists(): bool
    {
        try {
            $connection = config('error-tracker.database.connection');
            $connection = is_string($connection) && trim($connection) !== '' ? $connection : null;

            return Schema::connection($connection)->hasTable('error_tracker_external_issues');
        } catch (Throwable) {
            return false;
        }
    }
}
