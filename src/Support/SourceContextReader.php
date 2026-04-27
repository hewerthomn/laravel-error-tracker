<?php

namespace Hewerthomn\ErrorTracker\Support;

class SourceContextReader
{
    public function __construct(
        protected StackFrameClassifier $classifier,
    ) {}

    /**
     * @return array<int, array{number: int, content: string, highlight: bool}>|null
     */
    public function read(array $frame): ?array
    {
        if (! config('error-tracker.stacktrace.show_source_context', true)) {
            return null;
        }

        $file = $frame['file'] ?? null;
        $line = $frame['line'] ?? null;

        if (! is_string($file) || ! is_numeric($line) || ! $this->classifier->isProjectFile($file)) {
            return null;
        }

        $realFile = realpath($file);

        if ($realFile === false || ! is_file($realFile) || ! is_readable($realFile)) {
            return null;
        }

        if (! $this->classifier->isProjectFile($realFile)) {
            return null;
        }

        $lines = @file($realFile, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return null;
        }

        $targetLine = max(1, (int) $line);
        $contextLines = max(0, (int) config('error-tracker.stacktrace.source_context_lines', 5));
        $start = max(1, $targetLine - $contextLines);
        $end = min(count($lines), $targetLine + $contextLines);
        $context = [];

        for ($number = $start; $number <= $end; $number++) {
            $context[] = [
                'number' => $number,
                'content' => $lines[$number - 1] ?? '',
                'highlight' => $number === $targetLine,
            ];
        }

        return $context;
    }
}
