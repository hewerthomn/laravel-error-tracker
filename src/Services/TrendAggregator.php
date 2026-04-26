<?php

namespace Hewerthomn\ErrorTracker\Services;

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Models\IssueTrend;
use Illuminate\Support\Carbon;

class TrendAggregator
{
    public function increment(Issue $issue, \DateTimeInterface $occurredAt): void
    {
        $bucketStart = Carbon::instance(
            $occurredAt instanceof Carbon ? $occurredAt : Carbon::parse($occurredAt)
        )->startOfHour();

        $trend = IssueTrend::query()->firstOrCreate(
            [
                'issue_id' => $issue->id,
                'bucket_start' => $bucketStart,
                'bucket_granularity' => 'hour',
            ],
            [
                'events_count' => 0,
            ]
        );

        $trend->increment('events_count');
    }
}
