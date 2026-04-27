<?php

namespace Hewerthomn\ErrorTracker\Services;

use Hewerthomn\ErrorTracker\Contracts\ExceptionRecorder;
use Hewerthomn\ErrorTracker\Data\RecordedEventResult;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Support\StackFrameClassifier;
use Hewerthomn\ErrorTracker\Support\StackTrace\PathNormalizer;
use Hewerthomn\ErrorTracker\Support\StackTrace\SourceContextReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ErrorTrackerManager implements ExceptionRecorder
{
    public function __construct(
        protected FingerprintGenerator $fingerprintGenerator,
        protected SensitiveDataSanitizer $sanitizer,
        protected TrendAggregator $trendAggregator,
        protected IssueStatusService $issueStatusService,
        protected PathNormalizer $pathNormalizer,
        protected SourceContextReader $sourceContextReader,
        protected StackFrameClassifier $stackFrameClassifier,
    ) {}

    public function record(Throwable $throwable, array $context = []): RecordedEventResult
    {
        $occurredAt = now();
        $eventData = $this->buildEventData($throwable, $context, $occurredAt);
        $fingerprint = $this->fingerprintGenerator->generate($throwable, $eventData);

        return DB::transaction(function () use ($throwable, $eventData, $fingerprint, $occurredAt) {
            $issue = Issue::query()
                ->where('fingerprint', $fingerprint)
                ->where('environment', $eventData['environment'])
                ->first();

            $issueWasCreated = false;
            $issueWasReactivated = false;

            if (! $issue) {
                $issue = Issue::query()->create([
                    'fingerprint' => $fingerprint,
                    'title' => $this->buildIssueTitle($throwable),
                    'level' => $eventData['level'],
                    'status' => 'open',
                    'environment' => $eventData['environment'],
                    'exception_class' => $eventData['exception_class'],
                    'message_sample' => $this->truncateText($eventData['message'], 1000),
                    'first_seen_at' => $occurredAt,
                    'last_seen_at' => $occurredAt,
                    'total_events' => 0,
                    'affected_users' => 0,
                ]);

                $issueWasCreated = true;
            } elseif ($issue->status === 'resolved') {
                $issue = $this->issueStatusService->reopen($issue);

                $issueWasReactivated = true;
            }

            $affectedUserIncrement = $this->shouldIncrementAffectedUsers($issue, $eventData['user_id']);

            $event = $issue->events()->create([
                'uuid' => (string) Str::uuid(),
                'occurred_at' => $occurredAt,
                'level' => $eventData['level'],
                'exception_class' => $eventData['exception_class'],
                'message' => $eventData['message'],
                'file' => $eventData['file'],
                'line' => $eventData['line'],
                'request_method' => $eventData['request_method'],
                'request_path' => $eventData['request_path'],
                'route_name' => $eventData['route_name'],
                'url' => $eventData['url'],
                'status_code' => $eventData['status_code'],
                'command_name' => $eventData['command_name'],
                'job_name' => $eventData['job_name'],
                'environment' => $eventData['environment'],
                'release' => $eventData['release'],
                'user_id' => $eventData['user_id'],
                'user_type' => $eventData['user_type'],
                'user_label' => $eventData['user_label'],
                'ip_hash' => $eventData['ip_hash'],
                'trace_json' => $eventData['trace_json'],
                'context_json' => $eventData['context_json'],
                'headers_json' => $eventData['headers_json'],
                'feedback_token' => $eventData['feedback_token'],
            ]);

            $issue->forceFill([
                'level' => $eventData['level'],
                'last_seen_at' => $occurredAt,
                'last_event_id' => $event->id,
                'total_events' => $issue->total_events + 1,
                'affected_users' => $issue->affected_users + $affectedUserIncrement,
                'message_sample' => $this->truncateText($eventData['message'], 1000),
            ])->save();

            $this->trendAggregator->increment($issue, $occurredAt);

            return new RecordedEventResult(
                issue: $issue->fresh(['lastEvent']),
                event: $event->fresh(),
                issueWasCreated: $issueWasCreated,
                issueWasReactivated: $issueWasReactivated,
            );
        });
    }

    protected function buildEventData(Throwable $throwable, array $context, \DateTimeInterface $occurredAt): array
    {
        $request = $this->currentRequest();
        $userData = $this->extractUserData($request);
        $headers = $this->extractHeaders($request);
        $stackTrace = $this->buildStackTrace($throwable);
        $culpritFrame = $stackTrace['culprit_frame'];
        $throwingFrame = $stackTrace['throwing_frame'];

        return [
            'occurred_at' => $occurredAt,
            'level' => (string) ($context['level'] ?? 'error'),
            'exception_class' => $throwable::class,
            'message' => (string) $throwable->getMessage(),
            'file' => $culpritFrame['file'] ?? $throwingFrame['file'] ?? null,
            'line' => $culpritFrame['line'] ?? $throwingFrame['line'] ?? null,
            'request_method' => $request?->method(),
            'request_path' => $request?->path(),
            'route_name' => $request?->route()?->getName(),
            'url' => $request?->fullUrl(),
            'status_code' => method_exists($throwable, 'getStatusCode')
                ? $throwable->getStatusCode()
                : null,
            'command_name' => $context['command_name'] ?? $this->resolveCommandName(),
            'job_name' => $context['job_name'] ?? null,
            'environment' => app()->environment(),
            'release' => $context['release'] ?? config('app.version'),
            'user_id' => $userData['user_id'],
            'user_type' => $userData['user_type'],
            'user_label' => $userData['user_label'],
            'ip_hash' => $this->resolveIpHash($request),
            'trace_json' => $stackTrace['frames'],
            'context_json' => $this->sanitizer->sanitizeContext($context),
            'headers_json' => $headers,
            'feedback_token' => (string) Str::uuid(),
        ];
    }

    protected function buildIssueTitle(Throwable $throwable): string
    {
        $message = trim((string) $throwable->getMessage());

        if ($message === '') {
            return class_basename($throwable);
        }

        return sprintf(
            '%s: %s',
            class_basename($throwable),
            $this->truncateText($message, 160)
        );
    }

    protected function shouldIncrementAffectedUsers(Issue $issue, ?string $userId): int
    {
        if (! $userId) {
            return 0;
        }

        $alreadySeen = $issue->events()
            ->where('user_id', $userId)
            ->exists();

        return $alreadySeen ? 0 : 1;
    }

    protected function currentRequest(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return app('request');
    }

    protected function extractHeaders(?Request $request): ?array
    {
        if (! $request || ! config('error-tracker.capture.store_headers', true)) {
            return null;
        }

        return $this->sanitizer->sanitizeHeaders($request->headers->all());
    }

    protected function extractUserData(?Request $request): array
    {
        if (! $request || ! config('error-tracker.capture.store_user', true)) {
            return [
                'user_id' => null,
                'user_type' => null,
                'user_label' => null,
            ];
        }

        $user = $request->user();

        if (! $user) {
            return [
                'user_id' => null,
                'user_type' => null,
                'user_label' => null,
            ];
        }

        $authIdentifier = (string) $user->getAuthIdentifier();

        return [
            'user_id' => $authIdentifier,
            'user_type' => $user::class,
            'user_label' => $user->email ?? $user->name ?? $authIdentifier,
        ];
    }

    protected function resolveIpHash(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        $ip = $request->ip();

        if (! $ip) {
            return null;
        }

        if (! config('error-tracker.capture.hash_ip', true)) {
            return $ip;
        }

        return hash('sha256', $ip);
    }

    protected function resolveCommandName(): ?string
    {
        if (! app()->runningInConsole()) {
            return null;
        }

        $argv = $_SERVER['argv'] ?? [];

        if (! is_array($argv) || count($argv) === 0) {
            return null;
        }

        return implode(' ', $argv);
    }

    /**
     * @return array{
     *     frames: array<int, array<string, mixed>>,
     *     culprit_frame: array<string, mixed>|null,
     *     throwing_frame: array<string, mixed>|null
     * }
     */
    protected function buildStackTrace(Throwable $throwable): array
    {
        $maxFrames = (int) config('error-tracker.capture.max_trace_frames', 50);
        $frames = array_merge([
            [
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'function' => null,
                'class' => $throwable::class,
                'type' => null,
                'is_throwing_frame' => true,
            ],
        ], array_slice($throwable->getTrace(), 0, $maxFrames));

        $classifications = array_map(
            fn (array $frame): string => $this->stackFrameClassifier->classify($frame),
            $frames
        );
        $projectCulpritIndex = null;

        foreach ($classifications as $index => $classification) {
            if ($classification === 'project') {
                $projectCulpritIndex = $index;

                break;
            }
        }

        $fallbackToThrowingFrame = (bool) config('error-tracker.stacktrace.source_context.fallback_to_throwing_frame', true);
        $culpritIndex = $projectCulpritIndex ?? ($fallbackToThrowingFrame ? 0 : null);
        $eventLocationIndex = $projectCulpritIndex ?? 0;
        $maxSourceContextFrames = max(0, (int) config('error-tracker.stacktrace.source_context.max_frames', 5));
        $sourceContextFrames = 0;
        $normalizedFrames = [];

        foreach ($frames as $index => $frame) {
            $file = is_string($frame['file'] ?? null) ? $frame['file'] : null;
            $classification = $classifications[$index] ?? 'unknown';
            $isCulprit = $culpritIndex === $index;
            $sourceContext = null;
            $shouldReadSourceContext = $file !== null
                && $sourceContextFrames < $maxSourceContextFrames
                && (
                    $classification === 'project'
                    || ($isCulprit && $projectCulpritIndex === null && $fallbackToThrowingFrame)
                );

            if ($shouldReadSourceContext) {
                $sourceContext = $this->sourceContextReader->read($file, $frame['line'] ?? null);

                if ($sourceContext !== null) {
                    $sourceContextFrames++;
                }
            }

            $normalized = [
                'file' => $this->pathNormalizer->normalize($file),
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
                'type' => $frame['type'] ?? null,
                'classification' => $classification,
                'is_throwing_frame' => (bool) ($frame['is_throwing_frame'] ?? false),
                'is_culprit' => $isCulprit,
            ];

            if ($sourceContext !== null) {
                $normalized['source_context'] = $sourceContext;
            }

            if (config('error-tracker.stacktrace.store_arguments', false)) {
                $normalized['args'] = array_map(
                    fn (mixed $argument): string => is_object($argument) ? $argument::class : gettype($argument),
                    is_array($frame['args'] ?? null) ? $frame['args'] : []
                );
            }

            $normalizedFrames[] = $normalized;
        }

        return [
            'frames' => $normalizedFrames,
            'culprit_frame' => $normalizedFrames[$eventLocationIndex] ?? null,
            'throwing_frame' => $normalizedFrames[0],
        ];
    }

    protected function truncateText(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit($value, $limit);
    }
}
