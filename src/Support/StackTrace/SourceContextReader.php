<?php

namespace Hewerthomn\ErrorTracker\Support\StackTrace;

class SourceContextReader
{
    /**
     * @var array<int, string>
     */
    protected array $sensitiveFragments = [
        'token',
        'password',
        'secret',
        'authorization',
        'cookie',
        'x-api-key',
    ];

    public function __construct(
        protected PathNormalizer $pathNormalizer,
    ) {}

    /**
     * @return array{start_line: int, end_line: int, error_line: int, lines: array<int, array{number: int, code: string, is_error_line: bool}>}|null
     */
    public function read(mixed $file, mixed $line = null): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (is_array($file)) {
            $line = $file['line'] ?? null;
            $file = $file['file'] ?? null;
        }

        if (! is_string($file) || trim($file) === '' || ! is_numeric($line)) {
            return null;
        }

        $targetLine = (int) $line;

        if ($targetLine < 1) {
            return null;
        }

        $realFile = $this->resolveRealFile($file);

        if ($realFile === null || $this->isDisallowedFile($realFile)) {
            return null;
        }

        $maxSize = max(0, (int) config('error-tracker.stacktrace.source_context.max_file_size_kb', 512)) * 1024;

        if ($maxSize > 0 && filesize($realFile) > $maxSize) {
            return null;
        }

        $lines = @file($realFile, FILE_IGNORE_NEW_LINES);

        if ($lines === false || ! array_key_exists($targetLine - 1, $lines)) {
            return null;
        }

        $linesBefore = max(0, (int) config('error-tracker.stacktrace.source_context.lines_before', $this->legacyContextLines()));
        $linesAfter = max(0, (int) config('error-tracker.stacktrace.source_context.lines_after', $this->legacyContextLines()));
        $start = max(1, $targetLine - $linesBefore);
        $end = min(count($lines), $targetLine + $linesAfter);
        $contextLines = [];

        for ($number = $start; $number <= $end; $number++) {
            $contextLines[] = [
                'number' => $number,
                'code' => $this->maskSensitiveSourceLine($lines[$number - 1] ?? ''),
                'is_error_line' => $number === $targetLine,
            ];
        }

        return [
            'start_line' => $start,
            'end_line' => $end,
            'error_line' => $targetLine,
            'lines' => $contextLines,
        ];
    }

    protected function resolveRealFile(string $file): ?string
    {
        $normalizedFile = $this->pathNormalizer->normalizeSeparators($file);

        if (str_contains($normalizedFile, '../') || str_starts_with($normalizedFile, '..')) {
            return null;
        }

        $absolutePath = str_starts_with($normalizedFile, '/') || preg_match('/^[A-Za-z]:\//', $normalizedFile) === 1
            ? $normalizedFile
            : $this->pathNormalizer->toAbsoluteFromRelative($normalizedFile);

        if ($absolutePath === null) {
            return null;
        }

        $realFile = realpath($absolutePath);

        if ($realFile === false || ! is_file($realFile) || ! is_readable($realFile)) {
            return null;
        }

        return $this->pathNormalizer->normalizeSeparators($realFile);
    }

    protected function isDisallowedFile(string $realFile): bool
    {
        if (basename($realFile) === '.env') {
            return true;
        }

        $projectPaths = $this->projectPaths();

        if (! $this->pathNormalizer->isInsideAllowedPaths($realFile, $projectPaths)) {
            return true;
        }

        $sourceContextPaths = $this->configuredPaths('paths');

        if ($sourceContextPaths !== [] && ! $this->pathNormalizer->isInsideAllowedPaths($realFile, $sourceContextPaths)) {
            return true;
        }

        if ($this->pathNormalizer->isInsideAllowedPaths($realFile, $this->excludedPaths())) {
            return true;
        }

        if ((bool) config('error-tracker.stacktrace.source_context.project_only', true)) {
            return $this->pathNormalizer->isInsideAllowedPaths($realFile, [base_path('vendor')]);
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function projectPaths(): array
    {
        $paths = config('error-tracker.stacktrace.project_paths', []);

        if (! is_array($paths)) {
            return [];
        }

        return collect($paths)
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function excludedPaths(): array
    {
        $nonProjectPaths = config('error-tracker.stacktrace.non_project_paths', []);

        if (! is_array($nonProjectPaths)) {
            $nonProjectPaths = [];
        }

        return collect($this->configuredPaths('excluded_paths'))
            ->merge($nonProjectPaths)
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function configuredPaths(string $key): array
    {
        $paths = config('error-tracker.stacktrace.source_context.'.$key, []);

        if (! is_array($paths)) {
            return [];
        }

        return collect($paths)
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->values()
            ->all();
    }

    protected function isEnabled(): bool
    {
        if (! config('error-tracker.stacktrace.show_source_context', true)) {
            return false;
        }

        return (bool) config('error-tracker.stacktrace.source_context.enabled', true);
    }

    protected function legacyContextLines(): int
    {
        return max(0, (int) config('error-tracker.stacktrace.source_context_lines', 5));
    }

    protected function maskSensitiveSourceLine(string $line): string
    {
        $normalized = strtolower($line);

        foreach ($this->sensitiveFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return '[REDACTED SENSITIVE SOURCE LINE]';
            }
        }

        return $line;
    }
}
