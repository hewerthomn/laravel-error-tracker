<?php

namespace Hewerthomn\ErrorTracker\Support;

class StackTracePresenter
{
    public function __construct(
        protected StackFrameClassifier $classifier,
        protected SourceContextReader $sourceContextReader,
    ) {}

    /**
     * @return array{frames: array<int, array<string, mixed>>, first_project_frame: array<string, mixed>|null, has_frames: bool}
     */
    public function present(mixed $trace): array
    {
        $frames = $this->normalizeTrace($trace);
        $presentedFrames = [];
        $pendingGroup = [];
        $firstProjectFrame = null;
        $smartGrouping = (bool) config('error-tracker.stacktrace.smart_grouping', true);

        foreach ($frames as $index => $frame) {
            $presented = $this->presentFrame($frame, $index);

            if ($presented['classification'] === 'project') {
                $this->flushGroup($presentedFrames, $pendingGroup);
                $presentedFrames[] = $presented;
                $firstProjectFrame ??= $presented;

                continue;
            }

            if (! $smartGrouping) {
                $presentedFrames[] = $presented;

                continue;
            }

            $pendingGroup[] = $presented;
        }

        $this->flushGroup($presentedFrames, $pendingGroup);

        return [
            'frames' => $presentedFrames,
            'first_project_frame' => $firstProjectFrame,
            'has_frames' => $frames !== [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeTrace(mixed $trace): array
    {
        if (! is_array($trace)) {
            return [];
        }

        $frames = [];

        foreach ($trace as $frame) {
            if (! is_array($frame)) {
                continue;
            }

            unset($frame['args'], $frame['arguments']);

            $frames[] = $frame;
        }

        return $frames;
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentFrame(array $frame, int $index): array
    {
        $classification = $this->classifier->classify($frame);
        $file = $this->stringValue($frame['file'] ?? null);
        $line = is_numeric($frame['line'] ?? null) ? (int) $frame['line'] : null;
        $class = $this->stringValue($frame['class'] ?? null);
        $function = $this->stringValue($frame['function'] ?? null);
        $type = $this->stringValue($frame['type'] ?? null);
        $callable = trim(($class ?? '').($type ?? '').($function ?? 'unknown'));

        return [
            'type' => 'frame',
            'index' => $index,
            'classification' => $classification,
            'in_app' => $classification === 'project',
            'file' => $file,
            'relative_file' => $this->classifier->relativeFile($file),
            'line' => $line,
            'class' => $class,
            'function' => $function,
            'callable' => $callable !== '' ? $callable : 'unknown',
            'source_context' => $classification === 'project'
                ? $this->sourceContextReader->read(['file' => $file, 'line' => $line])
                : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $presentedFrames
     * @param  array<int, array<string, mixed>>  $pendingGroup
     */
    protected function flushGroup(array &$presentedFrames, array &$pendingGroup): void
    {
        if ($pendingGroup === []) {
            return;
        }

        $count = count($pendingGroup);
        $classifications = collect($pendingGroup)
            ->pluck('classification')
            ->unique()
            ->values();

        $presentedFrames[] = [
            'type' => 'group',
            'classification' => $classifications->count() === 1 ? $classifications->first() : 'non_project',
            'count' => $count,
            'label' => $count.' non-project '.str('frame')->plural($count),
            'collapsed' => (bool) config('error-tracker.stacktrace.collapse_non_project_frames', true),
            'frames' => $pendingGroup,
        ];

        $pendingGroup = [];
    }

    protected function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
