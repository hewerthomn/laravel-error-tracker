<?php

namespace Hewerthomn\ErrorTracker\Support\Dashboard;

use Hewerthomn\ErrorTracker\Models\Issue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class IssueSearchQuery
{
    /**
     * @param  Builder<Issue>  $query
     * @param  array<string, mixed>  $search
     * @return Builder<Issue>
     */
    public function apply(Builder $query, array $search): Builder
    {
        $this->applyIssueFilters($query, $search);
        $this->applyEventFilters($query, $search);
        $this->applyFeedbackFilters($query, $search);
        $this->applyPeriod($query, $search);

        return $query;
    }

    /**
     * @param  Builder<Issue>  $query
     * @param  array<string, mixed>  $search
     * @return Builder<Issue>
     */
    public function sort(Builder $query, array $search): Builder
    {
        $sort = is_string($search['sort'] ?? null) ? $search['sort'] : 'last_seen_at';
        $direction = ($search['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'first_seen_at' => $query->orderBy('first_seen_at', $direction)->orderBy('id', $direction),
            'total_events' => $query->orderBy('total_events', $direction)->orderByDesc('last_seen_at')->orderByDesc('id'),
            'affected_users' => $query->orderBy('affected_users', $direction)->orderByDesc('last_seen_at')->orderByDesc('id'),
            'level' => $query->orderBy('level', $direction)->orderByDesc('last_seen_at')->orderByDesc('id'),
            default => $query->orderBy('last_seen_at', $direction)->orderByDesc('id'),
        };

        return $query;
    }

    /**
     * @param  Builder<Issue>  $query
     * @param  array<string, mixed>  $search
     */
    protected function applyIssueFilters(Builder $query, array $search): void
    {
        if (($search['statuses'] ?? []) !== []) {
            $query->whereIn('status', $search['statuses']);
        }

        if (($search['levels'] ?? []) !== []) {
            $query->whereIn('level', $search['levels']);
        }

        if (($search['environments'] ?? []) !== []) {
            $query->whereIn('environment', $search['environments']);
        }

        $this->whereLike($query, 'exception_class', $search['exception_class'] ?? null);
        $this->whereLike($query, 'message_sample', $search['message'] ?? null);
        $this->whereLike($query, 'fingerprint', $search['fingerprint'] ?? null);
        $this->whereExact($query, 'resolved_by_type', $search['resolved_by_type'] ?? null);

        if (($search['text'] ?? '') !== '') {
            $text = $this->likeValue((string) $search['text']);

            $query->where(function (Builder $subQuery) use ($text): void {
                $subQuery
                    ->where('title', 'like', $text)
                    ->orWhere('exception_class', 'like', $text)
                    ->orWhere('message_sample', 'like', $text)
                    ->orWhere('fingerprint', 'like', $text)
                    ->orWhere('status', 'like', $text)
                    ->orWhere('level', 'like', $text)
                    ->orWhere('environment', 'like', $text)
                    ->orWhere('resolved_by_type', 'like', $text)
                    ->orWhere('resolved_reason', 'like', $text);
            });
        }
    }

    /**
     * @param  Builder<Issue>  $query
     * @param  array<string, mixed>  $search
     */
    protected function applyEventFilters(Builder $query, array $search): void
    {
        $eventFilters = [
            'route_name' => $search['route'] ?? null,
            'request_path' => $search['path'] ?? null,
            'url' => $search['url'] ?? null,
            'file' => $search['file'] ?? null,
        ];
        $user = $this->stringValue($search['user'] ?? null);
        $statusCode = is_int($search['status_code'] ?? null) ? $search['status_code'] : null;

        if (! $this->hasEventFilters($eventFilters, $user, $statusCode)) {
            return;
        }

        $query->whereHas('events', function (Builder $eventQuery) use ($eventFilters, $user, $statusCode): void {
            foreach ($eventFilters as $column => $value) {
                $this->whereLike($eventQuery, $column, $value);
            }

            if ($user !== null) {
                $like = $this->likeValue($user);

                $eventQuery->where(function (Builder $userQuery) use ($like): void {
                    $userQuery
                        ->where('user_id', 'like', $like)
                        ->orWhere('user_type', 'like', $like)
                        ->orWhere('user_label', 'like', $like);
                });
            }

            if ($statusCode !== null) {
                $eventQuery->where('status_code', $statusCode);
            }
        });
    }

    /**
     * @param  Builder<Issue>  $query
     * @param  array<string, mixed>  $search
     */
    protected function applyFeedbackFilters(Builder $query, array $search): void
    {
        if (($search['has_feedback'] ?? null) === true) {
            $query->whereHas('events.feedback');
        } elseif (($search['has_feedback'] ?? null) === false) {
            $query->whereDoesntHave('events.feedback');
        }
    }

    /**
     * @param  Builder<Issue>  $query
     * @param  array<string, mixed>  $search
     */
    protected function applyPeriod(Builder $query, array $search): void
    {
        [$from, $to] = $this->periodRange($search);

        if ($from !== null && $to !== null) {
            $query->whereBetween('last_seen_at', [$from, $to]);
        } elseif ($from !== null) {
            $query->where('last_seen_at', '>=', $from);
        } elseif ($to !== null) {
            $query->where('last_seen_at', '<=', $to);
        }
    }

    /**
     * @param  array<string, mixed>  $search
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    public function periodRange(array $search): array
    {
        $period = is_string($search['period'] ?? null) ? $search['period'] : 'all';

        if ($period === 'custom') {
            return [
                $this->parseDate($search['from'] ?? null, startOfDay: true),
                $this->parseDate($search['to'] ?? null, startOfDay: false),
            ];
        }

        $to = now();

        return match ($period) {
            '1h' => [$to->copy()->subHour(), $to],
            '24h' => [$to->copy()->subDay(), $to],
            '7d' => [$to->copy()->subDays(7), $to],
            '30d' => [$to->copy()->subDays(30), $to],
            default => [null, null],
        };
    }

    protected function whereLike(Builder $query, string $column, mixed $value): void
    {
        $value = $this->stringValue($value);

        if ($value !== null) {
            $query->where($column, 'like', $this->likeValue($value));
        }
    }

    protected function whereExact(Builder $query, string $column, mixed $value): void
    {
        $value = $this->stringValue($value);

        if ($value !== null) {
            $query->where($column, $value);
        }
    }

    protected function likeValue(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value).'%';
    }

    /**
     * @param  array<string, mixed>  $eventFilters
     */
    protected function hasEventFilters(array $eventFilters, ?string $user, ?int $statusCode): bool
    {
        foreach ($eventFilters as $value) {
            if ($this->stringValue($value) !== null) {
                return true;
            }
        }

        return $user !== null || $statusCode !== null;
    }

    protected function stringValue(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function parseDate(mixed $value, bool $startOfDay): ?Carbon
    {
        $value = $this->stringValue($value);

        if ($value === null) {
            return null;
        }

        $date = Carbon::parse($value);

        return str_contains($value, ':')
            ? $date
            : ($startOfDay ? $date->startOfDay() : $date->endOfDay());
    }
}
