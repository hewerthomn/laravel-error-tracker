<?php

namespace Hewerthomn\ErrorTracker\Services;

use DateTimeInterface;
use Hewerthomn\ErrorTracker\Models\Issue;

class IssueStatusService
{
    public function resolve(Issue $issue): Issue
    {
        $issue->forceFill([
            'status' => 'resolved',
            'resolved_at' => now(),
            'ignored_at' => null,
            'muted_until' => null,
            'mute_reason' => null,
        ])->save();

        return $issue->fresh();
    }

    public function reopen(Issue $issue): Issue
    {
        $issue->forceFill([
            'status' => 'open',
            'resolved_at' => null,
            'ignored_at' => null,
            'muted_until' => null,
            'mute_reason' => null,
        ])->save();

        return $issue->fresh();
    }

    public function ignore(Issue $issue): Issue
    {
        $issue->forceFill([
            'status' => 'ignored',
            'resolved_at' => null,
            'ignored_at' => now(),
            'muted_until' => null,
            'mute_reason' => null,
        ])->save();

        return $issue->fresh();
    }

    public function mute(Issue $issue, ?DateTimeInterface $until = null, ?string $reason = null): Issue
    {
        $issue->forceFill([
            'status' => 'muted',
            'resolved_at' => null,
            'ignored_at' => null,
            'muted_until' => $until,
            'mute_reason' => $reason,
        ])->save();

        return $issue->fresh();
    }

    public function unmute(Issue $issue): Issue
    {
        $issue->forceFill([
            'status' => 'open',
            'muted_until' => null,
            'mute_reason' => null,
        ])->save();

        return $issue->fresh();
    }
}
