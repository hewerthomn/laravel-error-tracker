<?php

namespace Hewerthomn\ErrorTracker\Actions;

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Services\IssueStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AutoResolveIssuesAction
{
    public function __construct(
        protected IssueStatusService $issueStatusService,
    ) {}

    /**
     * @return array{enabled: bool, dry_run: bool, cutoff: Carbon, found: int, resolved: int}
     */
    public function handle(bool $dryRun = false, ?int $afterDays = null): array
    {
        $enabled = (bool) config('error-tracker.auto_resolve.enabled', false);
        $days = $afterDays ?? (int) config('error-tracker.auto_resolve.after_days', 14);
        $cutoff = now()->subDays($days);

        if (! $enabled) {
            return [
                'enabled' => false,
                'dry_run' => $dryRun,
                'cutoff' => $cutoff,
                'found' => 0,
                'resolved' => 0,
            ];
        }

        $query = $this->eligibleIssuesQuery($cutoff);
        $found = (clone $query)->count();
        $resolved = 0;

        if (! $dryRun) {
            $reason = $this->autoResolveReason($days);

            $query->get()->each(function (Issue $issue) use (&$resolved, $reason): void {
                $this->issueStatusService->resolveAutomatically($issue, $reason);

                $resolved++;
            });
        }

        return [
            'enabled' => true,
            'dry_run' => $dryRun,
            'cutoff' => $cutoff,
            'found' => $found,
            'resolved' => $resolved,
        ];
    }

    /**
     * @return Builder<Issue>
     */
    protected function eligibleIssuesQuery(Carbon $cutoff): Builder
    {
        $statuses = $this->configuredList('error-tracker.auto_resolve.statuses', ['open']);
        $levels = $this->configuredList('error-tracker.auto_resolve.levels', ['warning', 'error']);
        $environments = $this->configuredList('error-tracker.auto_resolve.environments');

        return Issue::query()
            ->whereIn('status', $statuses)
            ->where('last_seen_at', '<=', $cutoff)
            ->when($levels !== [], fn (Builder $query) => $query->whereIn('level', $levels))
            ->when($environments !== [], fn (Builder $query) => $query->whereIn('environment', $environments));
    }

    /**
     * @return array<int, string>
     */
    protected function configuredList(string $key, ?array $default = null): array
    {
        $value = config($key, $default);

        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($item) => is_scalar($item) && (string) $item !== '')
            ->map(fn ($item) => (string) $item)
            ->values()
            ->all();
    }

    protected function autoResolveReason(int $days): string
    {
        $reason = (string) config(
            'error-tracker.auto_resolve.reason',
            'Automatically resolved after :days days without new events.'
        );

        return str_replace(':days', (string) $days, $reason);
    }
}
