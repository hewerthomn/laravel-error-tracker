<?php

namespace Hewerthomn\ErrorTracker\Commands;

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Feedback;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Models\IssueTrend;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PruneCommand extends Command
{
    protected $signature = 'error-tracker:prune {--dry-run : Display what would be deleted without deleting records}';

    protected $description = 'Prune old Error Tracker data';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $eventsDays = (int) config('error-tracker.retention.events_days', 30);
        $resolvedIssuesDays = (int) config('error-tracker.retention.resolved_issues_days', 90);
        $feedbackDays = (int) config('error-tracker.retention.delete_feedback_after_days', 90);

        $eventsBefore = now()->subDays($eventsDays);
        $resolvedIssuesBefore = now()->subDays($resolvedIssuesDays);
        $feedbackBefore = now()->subDays($feedbackDays);

        $this->info('Starting Error Tracker prune...');
        $this->line('Dry run: '.($isDryRun ? 'yes' : 'no'));

        $feedbackQuery = Feedback::query()->where('created_at', '<', $feedbackBefore);
        $feedbackCount = (clone $feedbackQuery)->count();

        $staleResolvedIssuesQuery = Issue::query()
            ->whereIn('status', ['resolved', 'ignored'])
            ->where('last_seen_at', '<', $resolvedIssuesBefore);

        $staleResolvedIssueCount = (clone $staleResolvedIssuesQuery)->count();

        $oldEventIssueIds = Event::query()
            ->where('occurred_at', '<', $eventsBefore)
            ->pluck('issue_id')
            ->unique()
            ->values();

        $oldEventsQuery = Event::query()->where('occurred_at', '<', $eventsBefore);
        $oldEventsCount = (clone $oldEventsQuery)->count();

        $oldTrendsQuery = IssueTrend::query()->where('bucket_start', '<', $eventsBefore->copy()->startOfHour());
        $oldTrendsCount = (clone $oldTrendsQuery)->count();

        $this->line('Old feedback rows: '.$feedbackCount);
        $this->line('Old events: '.$oldEventsCount);
        $this->line('Old trend buckets: '.$oldTrendsCount);
        $this->line('Old resolved or ignored issues: '.$staleResolvedIssueCount);

        if ($isDryRun) {
            return self::SUCCESS;
        }

        $deletedFeedback = $feedbackQuery->delete();
        $deletedTrends = $oldTrendsQuery->delete();
        $deletedEvents = $oldEventsQuery->delete();
        $deletedResolvedIssues = $staleResolvedIssuesQuery->delete();

        $this->recalculateIssues($oldEventIssueIds);

        $orphanIssues = Issue::query()->doesntHave('events')->get();
        $deletedOrphanIssues = $orphanIssues->count();

        foreach ($orphanIssues as $issue) {
            $issue->delete();
        }

        $this->info('Prune finished.');
        $this->line('Deleted feedback rows: '.$deletedFeedback);
        $this->line('Deleted events: '.$deletedEvents);
        $this->line('Deleted trend buckets: '.$deletedTrends);
        $this->line('Deleted stale resolved or ignored issues: '.$deletedResolvedIssues);
        $this->line('Deleted orphan issues: '.$deletedOrphanIssues);

        return self::SUCCESS;
    }

    protected function recalculateIssues(Collection $issueIds): void
    {
        if ($issueIds->isEmpty()) {
            return;
        }

        Issue::query()
            ->whereIn('id', $issueIds->all())
            ->get()
            ->each(function (Issue $issue) {
                $eventsQuery = $issue->events();

                $totalEvents = (clone $eventsQuery)->count();

                if ($totalEvents === 0) {
                    return;
                }

                $firstSeenAt = (clone $eventsQuery)->min('occurred_at');
                $lastSeenAt = (clone $eventsQuery)->max('occurred_at');

                $lastEvent = (clone $eventsQuery)
                    ->latest('occurred_at')
                    ->latest('id')
                    ->first();

                $affectedUsers = (clone $eventsQuery)
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->count('user_id');

                $issue->forceFill([
                    'first_seen_at' => $firstSeenAt,
                    'last_seen_at' => $lastSeenAt,
                    'total_events' => $totalEvents,
                    'affected_users' => $affectedUsers,
                    'last_event_id' => $lastEvent?->id,
                    'message_sample' => $lastEvent?->message,
                    'level' => $lastEvent?->level ?? $issue->level,
                    'exception_class' => $lastEvent?->exception_class ?? $issue->exception_class,
                ])->save();
            });
    }
}
