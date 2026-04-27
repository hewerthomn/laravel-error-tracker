<?php

namespace Hewerthomn\ErrorTracker\Support;

use Hewerthomn\ErrorTracker\Support\StackTrace\PathNormalizer;
use Hewerthomn\ErrorTracker\Support\StackTrace\SourceContextReader as StackTraceSourceContextReader;

class SourceContextReader extends StackTraceSourceContextReader
{
    public function __construct(
        protected StackFrameClassifier $classifier,
    ) {
        parent::__construct(new PathNormalizer);
    }

    /**
     * @return array<int, array{number: int, content: string, highlight: bool}>|null
     */
    public function readLegacy(array $frame): ?array
    {
        $file = $frame['file'] ?? null;
        $context = parent::read(is_string($file) ? $file : null, $frame['line'] ?? null);

        return collect($context['lines'] ?? [])
            ->map(fn (array $line): array => [
                'number' => $line['number'],
                'content' => $line['code'],
                'highlight' => $line['is_error_line'],
            ])
            ->all() ?: null;
    }
}
