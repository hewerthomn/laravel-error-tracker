<?php

namespace Hewerthomn\ErrorTracker\Support\Dashboard;

use Illuminate\Support\Arr;

class IssueSearchParser
{
    /**
     * @var array<int, string>
     */
    protected array $allowedStatuses = ['open', 'resolved', 'ignored', 'muted'];

    /**
     * @var array<int, string>
     */
    protected array $allowedLevels = ['error', 'warning', 'critical', 'info', 'debug'];

    /**
     * @var array<int, string>
     */
    protected array $allowedResolvedByTypes = ['auto', 'manual'];

    /**
     * @return array{
     *     text: string,
     *     statuses: array<int, string>,
     *     levels: array<int, string>,
     *     environments: array<int, string>,
     *     exception_class: string|null,
     *     message: string|null,
     *     fingerprint: string|null,
     *     route: string|null,
     *     path: string|null,
     *     url: string|null,
     *     file: string|null,
     *     user: string|null,
     *     status_code: int|null,
     *     resolved_by_type: string|null,
     *     has_feedback: bool|null,
     *     period: string,
     *     from: string|null,
     *     to: string|null,
     *     sort: string,
     *     direction: string
     * }
     */
    public function parse(string $query, array $filters = []): array
    {
        $parsed = $this->defaults();
        $parsed['text'] = $this->extractOperators($query, $parsed);

        $parsed['statuses'] = $this->mergeAllowedValues(
            $parsed['statuses'],
            $this->arrayInput($filters, 'status'),
            $this->allowedStatuses
        );
        $parsed['levels'] = $this->mergeAllowedValues(
            $parsed['levels'],
            $this->arrayInput($filters, 'level'),
            $this->allowedLevels
        );
        $parsed['environments'] = $this->mergeStringValues(
            $parsed['environments'],
            array_merge($this->arrayInput($filters, 'environment'), $this->arrayInput($filters, 'env'))
        );

        $this->fillStringFilter($parsed, 'exception_class', $filters['exception_class'] ?? $filters['exception'] ?? $filters['class'] ?? null);
        $this->fillStringFilter($parsed, 'message', $filters['message'] ?? null);
        $this->fillStringFilter($parsed, 'fingerprint', $filters['fingerprint'] ?? null);
        $this->fillStringFilter($parsed, 'route', $filters['route'] ?? null);
        $this->fillStringFilter($parsed, 'path', $filters['path'] ?? null);
        $this->fillStringFilter($parsed, 'url', $filters['url'] ?? null);
        $this->fillStringFilter($parsed, 'file', $filters['file'] ?? null);
        $this->fillStringFilter($parsed, 'user', $filters['user'] ?? null);

        $statusCode = $this->statusCode($filters['status_code'] ?? null);

        if ($statusCode !== null) {
            $parsed['status_code'] = $statusCode;
        }

        $resolvedByType = $this->allowedValue($filters['resolved_by_type'] ?? $filters['resolved'] ?? null, $this->allowedResolvedByTypes);

        if ($resolvedByType !== null) {
            $parsed['resolved_by_type'] = $resolvedByType;
        }

        $hasFeedback = $this->hasFeedback($filters['has_feedback'] ?? $filters['has'] ?? null);

        if ($hasFeedback !== null) {
            $parsed['has_feedback'] = $hasFeedback;
        }

        $parsed['period'] = $this->period($filters['period'] ?? null, $filters);
        $parsed['from'] = $this->dateValue($filters['from'] ?? null);
        $parsed['to'] = $this->dateValue($filters['to'] ?? null);
        $parsed['sort'] = $this->sort($filters['sort'] ?? null);
        $parsed['direction'] = $this->direction($filters['direction'] ?? null, $parsed['sort']);

        return $parsed;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'text' => '',
            'statuses' => [],
            'levels' => [],
            'environments' => [],
            'exception_class' => null,
            'message' => null,
            'fingerprint' => null,
            'route' => null,
            'path' => null,
            'url' => null,
            'file' => null,
            'user' => null,
            'status_code' => null,
            'resolved_by_type' => null,
            'has_feedback' => null,
            'period' => 'all',
            'from' => null,
            'to' => null,
            'sort' => 'last_seen_at',
            'direction' => 'desc',
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function extractOperators(string $query, array &$parsed): string
    {
        $text = preg_replace_callback(
            '/(?<!\S)([A-Za-z_]+):(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\']*)*)\'|([^"\s]+))/',
            function (array $matches) use (&$parsed): string {
                $operator = strtolower((string) $matches[1]);
                $value = $this->unquote($this->matchedValue($matches));

                return $this->applyOperator($parsed, $operator, $value)
                    ? ' '
                    : (string) $matches[0];
            },
            $query
        ) ?? $query;

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function applyOperator(array &$parsed, string $operator, string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return true;
        }

        return match ($operator) {
            'status' => $this->appendAllowed($parsed, 'statuses', $value, $this->allowedStatuses),
            'level' => $this->appendAllowed($parsed, 'levels', $value, $this->allowedLevels),
            'env', 'environment' => $this->appendString($parsed, 'environments', $value),
            'class', 'exception' => $this->setString($parsed, 'exception_class', $value),
            'message' => $this->setString($parsed, 'message', $value),
            'fingerprint' => $this->setString($parsed, 'fingerprint', $value),
            'route' => $this->setString($parsed, 'route', $value),
            'path' => $this->setString($parsed, 'path', $value),
            'url' => $this->setString($parsed, 'url', $value),
            'file' => $this->setString($parsed, 'file', $value),
            'user' => $this->setString($parsed, 'user', $value),
            'status_code' => $this->setStatusCode($parsed, $value),
            'resolved' => $this->setAllowed($parsed, 'resolved_by_type', $value, $this->allowedResolvedByTypes),
            'has' => $this->setHasFeedback($parsed, $value),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<int, string>  $allowed
     */
    protected function appendAllowed(array &$parsed, string $key, string $value, array $allowed): bool
    {
        $value = $this->allowedValue($value, $allowed);

        if ($value === null) {
            return true;
        }

        $parsed[$key] = array_values(array_unique(array_merge($parsed[$key], [$value])));

        return true;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function appendString(array &$parsed, string $key, string $value): bool
    {
        $parsed[$key] = array_values(array_unique(array_merge($parsed[$key], [$value])));

        return true;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function setString(array &$parsed, string $key, string $value): bool
    {
        $parsed[$key] = $value;

        return true;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<int, string>  $allowed
     */
    protected function setAllowed(array &$parsed, string $key, string $value, array $allowed): bool
    {
        $value = $this->allowedValue($value, $allowed);

        if ($value !== null) {
            $parsed[$key] = $value;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function setStatusCode(array &$parsed, string $value): bool
    {
        $statusCode = $this->statusCode($value);

        if ($statusCode !== null) {
            $parsed['status_code'] = $statusCode;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function setHasFeedback(array &$parsed, string $value): bool
    {
        $hasFeedback = $this->hasFeedback($value);

        if ($hasFeedback !== null) {
            $parsed['has_feedback'] = $hasFeedback;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function fillStringFilter(array &$parsed, string $key, mixed $value): void
    {
        if (is_string($value) || is_numeric($value)) {
            $value = trim((string) $value);

            if ($value !== '') {
                $parsed[$key] = $value;
            }
        }
    }

    /**
     * @param  array<int, string>  $existing
     * @param  array<int, mixed>  $values
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    protected function mergeAllowedValues(array $existing, array $values, array $allowed): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $allowedValue = $this->allowedValue($value, $allowed);

            if ($allowedValue !== null) {
                $normalized[] = $allowedValue;
            }
        }

        return array_values(array_unique(array_merge($existing, $normalized)));
    }

    /**
     * @param  array<int, string>  $existing
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected function mergeStringValues(array $existing, array $values): array
    {
        $normalized = collect($values)
            ->filter(fn ($value) => is_string($value) || is_numeric($value))
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->values()
            ->all();

        return array_values(array_unique(array_merge($existing, $normalized)));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, mixed>
     */
    protected function arrayInput(array $filters, string $key): array
    {
        $value = Arr::get($filters, $key, []);

        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return $value;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    protected function allowedValue(mixed $value, array $allowed): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, $allowed, true) ? $value : null;
    }

    protected function statusCode(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $statusCode = (int) $value;

        return $statusCode >= 100 && $statusCode <= 599 ? $statusCode : null;
    }

    protected function hasFeedback(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return match ($value) {
            'feedback', '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function period(mixed $period, array $filters): string
    {
        if (($this->dateValue($filters['from'] ?? null) !== null) || ($this->dateValue($filters['to'] ?? null) !== null)) {
            return 'custom';
        }

        if (! is_string($period) && ! is_numeric($period)) {
            return 'all';
        }

        $period = trim((string) $period);

        return in_array($period, ['1h', '24h', '7d', '30d', 'all'], true) ? $period : 'all';
    }

    protected function dateValue(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return strtotime($value) !== false ? $value : null;
    }

    protected function sort(mixed $sort): string
    {
        if (! is_string($sort) && ! is_numeric($sort)) {
            return 'last_seen_at';
        }

        return match (trim((string) $sort)) {
            'recent' => 'last_seen_at',
            'frequent' => 'total_events',
            'oldest' => 'first_seen_at',
            'last_seen_at', 'first_seen_at', 'total_events', 'affected_users', 'level' => trim((string) $sort),
            default => 'last_seen_at',
        };
    }

    protected function direction(mixed $direction, string $sort): string
    {
        if (! is_string($direction) && ! is_numeric($direction)) {
            return $sort === 'first_seen_at' ? 'asc' : 'desc';
        }

        $direction = strtolower(trim((string) $direction));

        return in_array($direction, ['asc', 'desc'], true) ? $direction : ($sort === 'first_seen_at' ? 'asc' : 'desc');
    }

    protected function unquote(string $value): string
    {
        return stripcslashes($value);
    }

    /**
     * @param  array<int, string>  $matches
     */
    protected function matchedValue(array $matches): string
    {
        foreach ([2, 3, 4] as $index) {
            if (($matches[$index] ?? '') !== '') {
                return (string) $matches[$index];
            }
        }

        return '';
    }
}
