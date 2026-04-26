<?php

namespace Hewerthomn\ErrorTracker\Services;

use Throwable;

class FingerprintGenerator
{
    public function generate(Throwable $throwable, array $eventData): string
    {
        $exceptionClass = $throwable::class;
        $message = $this->normalizeMessage((string) $throwable->getMessage());
        $location = $this->resolveLocationKey($eventData);
        $firstFrame = $this->resolveFirstApplicationFrame($throwable->getTrace());

        $parts = [
            $exceptionClass,
            $message,
            $firstFrame,
            $location,
        ];

        if (config('error-tracker.fingerprint.include_environment', false)) {
            $parts[] = (string) ($eventData['environment'] ?? app()->environment());
        }

        return sha1(implode('|', $parts));
    }

    protected function normalizeMessage(string $message): string
    {
        $normalized = trim($message);

        if ($normalized === '') {
            return 'no-message';
        }

        if (config('error-tracker.fingerprint.normalize_uuids', true)) {
            $normalized = preg_replace(
                '/\b[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\b/i',
                ':uuid',
                $normalized
            );
        }

        if (config('error-tracker.fingerprint.normalize_emails', true)) {
            $normalized = preg_replace(
                '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
                ':email',
                $normalized
            );
        }

        if (config('error-tracker.fingerprint.normalize_tokens', true)) {
            $normalized = preg_replace('/\b[a-f0-9]{32,}\b/i', ':token', $normalized);
        }

        if (config('error-tracker.fingerprint.normalize_ids', true)) {
            $normalized = preg_replace('/\b\d{3,}\b/', ':id', $normalized);
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    protected function resolveLocationKey(array $eventData): string
    {
        return (string) (
            $eventData['route_name']
            ?? $eventData['request_path']
            ?? $eventData['command_name']
            ?? $eventData['job_name']
            ?? 'unknown-location'
        );
    }

    protected function resolveFirstApplicationFrame(array $trace): string
    {
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? null;

            if (! is_string($file) || $file === '') {
                continue;
            }

            if (str_contains($file, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            return sprintf('%s:%s', $file, (string) ($frame['line'] ?? '0'));
        }

        $first = $trace[0] ?? [];

        return sprintf(
            '%s:%s',
            (string) ($first['file'] ?? 'unknown-file'),
            (string) ($first['line'] ?? '0')
        );
    }
}
