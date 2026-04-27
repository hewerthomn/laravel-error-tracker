<?php

namespace Hewerthomn\ErrorTracker\Services;

use DateTimeInterface;
use Hewerthomn\ErrorTracker\Models\Issue;

class IssueStatusService
{
    public function resolve(Issue $issue, ?string $reason = null): Issue
    {
        return $this->resolveManually($issue, $reason);
    }

    public function resolveManually(Issue $issue, ?string $reason = null): Issue
    {
        $issue->forceFill([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by_type' => 'manual',
            'resolved_reason' => $reason,
            'ignored_at' => null,
            'muted_until' => null,
            'mute_reason' => null,
        ])->save();

        return $issue->fresh();
    }

    public function resolveAutomatically(Issue $issue, ?string $reason = null): Issue
    {
        $issue->forceFill([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by_type' => 'auto',
            'resolved_reason' => $reason ?? $this->defaultAutoResolveReason(),
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
            'resolved_by_type' => null,
            'resolved_reason' => null,
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
            'resolved_by_type' => null,
            'resolved_reason' => null,
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
            'resolved_by_type' => null,
            'resolved_reason' => null,
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
            'resolved_at' => null,
            'resolved_by_type' => null,
            'resolved_reason' => null,
            'muted_until' => null,
            'mute_reason' => null,
        ])->save();

        return $issue->fresh();
    }

    protected function defaultAutoResolveReason(): string
    {
        $days = (int) config('error-tracker.auto_resolve.after_days', 14);
        $reason = (string) config(
            'error-tracker.auto_resolve.reason',
            'Automatically resolved after :days days without new events.'
        );

        return str_replace(':days', (string) $days, $reason);
    }
}
