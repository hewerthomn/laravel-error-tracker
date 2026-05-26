<?php

namespace Hewerthomn\ErrorTracker\Actions;

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Services\SensitiveDataSanitizer;
use Hewerthomn\ErrorTracker\Support\StackFrameClassifier;
use Illuminate\Support\Str;
use Throwable;

class BuildExternalIssueBodyAction
{
    public function __construct(
        protected StackFrameClassifier $stackFrameClassifier,
        protected SensitiveDataSanitizer $sanitizer,
    ) {}

    public function execute(Issue $issue): string
    {
        $issue->loadMissing('lastEvent');
        $event = $issue->lastEvent;

        $lines = [
            '## Error Tracker issue',
            '',
        ];

        if ($url = $this->internalIssueUrl($issue)) {
            $lines[] = '- Internal issue: '.$url;
        }

        $lines = array_merge($lines, [
            '- Exception: '.$this->safeText($issue->exception_class ?: $event?->exception_class ?: 'Unknown exception'),
            '- Level: '.$this->safeText($issue->level),
            '- Environment: '.$this->safeText($issue->environment),
            '- First seen: '.$this->dateValue($issue->first_seen_at),
            '- Last seen: '.$this->dateValue($issue->last_seen_at),
            '- Occurrences: '.number_format((int) $issue->total_events),
            '- Route/path: '.$this->routeOrPath($event),
            '- Location: '.$this->location($event),
            '',
            '## Message',
            '',
            $this->safeText($issue->message_sample ?: $event?->message ?: 'No message sample recorded.'),
        ]);

        if ((bool) config('error-tracker.integrations.issue_trackers.github.include_stack_trace', true)) {
            $lines = array_merge($lines, $this->stackTraceLines($event));
        }

        if ((bool) config('error-tracker.integrations.issue_trackers.github.include_context', false)) {
            $lines = array_merge($lines, $this->contextLines($event));
        }

        if ((bool) config('error-tracker.integrations.issue_trackers.github.include_source_context', false)) {
            $lines = array_merge($lines, $this->sourceContextLines($event));
        }

        return trim(implode("\n", $lines))."\n";
    }

    protected function internalIssueUrl(Issue $issue): ?string
    {
        try {
            return route('error-tracker.issues.show', $issue);
        } catch (Throwable) {
            return null;
        }
    }

    protected function dateValue(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d H:i:s') : 'unknown';
    }

    protected function routeOrPath(?Event $event): string
    {
        if (! $event) {
            return 'unknown';
        }

        return $this->safeText($event->route_name ?: $event->request_path ?: $event->command_name ?: $event->job_name ?: 'unknown');
    }

    protected function location(?Event $event): string
    {
        if (! $event) {
            return 'unknown';
        }

        $file = $this->stackFrameClassifier->relativeFile($event->file) ?: $event->file;

        if (! $file) {
            return 'unknown';
        }

        return $this->safeText($file.($event->line ? ':'.$event->line : ''));
    }

    /**
     * @return array<int, string>
     */
    protected function stackTraceLines(?Event $event): array
    {
        $frames = is_array($event?->trace_json) ? $event->trace_json : [];
        $maxFrames = max(0, (int) config('error-tracker.integrations.issue_trackers.github.max_stack_frames', 15));
        $applicationFrames = collect($frames)
            ->filter(fn (mixed $frame): bool => is_array($frame) && $this->stackFrameClassifier->classify($frame) === 'project')
            ->take($maxFrames)
            ->values();

        if ($applicationFrames->isEmpty()) {
            return [];
        }

        $lines = [
            '',
            '## Application stack frames',
            '',
            '```text',
        ];

        foreach ($applicationFrames as $index => $frame) {
            $file = $this->stackFrameClassifier->relativeFile($this->stringValue($frame['file'] ?? null)) ?: '[internal]';
            $line = isset($frame['line']) ? ':'.(int) $frame['line'] : '';
            $callable = trim(($this->stringValue($frame['class'] ?? null) ?? '').($this->stringValue($frame['type'] ?? null) ?? '').($this->stringValue($frame['function'] ?? null) ?? ''));

            $lines[] = '#'.($index + 1).' '.$file.$line.($callable !== '' ? ' '.$callable : '');
        }

        $lines[] = '```';

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    protected function contextLines(?Event $event): array
    {
        if (! is_array($event?->context_json) || $event->context_json === []) {
            return [];
        }

        return [
            '',
            '## Redacted context',
            '',
            '```json',
            json_encode($this->sanitizer->sanitizeContext($event->context_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
            '```',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sourceContextLines(?Event $event): array
    {
        $frames = is_array($event?->trace_json) ? $event->trace_json : [];
        $sourceContexts = collect($frames)
            ->filter(fn (mixed $frame): bool => is_array($frame) && ! empty($frame['source_context']))
            ->take(3)
            ->values();

        if ($sourceContexts->isEmpty()) {
            return [];
        }

        $lines = [
            '',
            '## Redacted source context',
        ];

        foreach ($sourceContexts as $frame) {
            $file = $this->stackFrameClassifier->relativeFile($this->stringValue($frame['file'] ?? null)) ?: 'unknown file';
            $lines[] = '';
            $lines[] = '### '.$this->safeText($file);
            $lines[] = '';
            $lines[] = '```text';
            $lines[] = $this->safeText(json_encode($this->sanitizer->sanitizeArray((array) $frame['source_context']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');
            $lines[] = '```';
        }

        return $lines;
    }

    protected function safeText(string $value): string
    {
        $value = preg_replace('/[[:cntrl:]]+/', ' ', $value) ?? $value;

        return Str::limit(trim($value), 2000, '...');
    }

    protected function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
