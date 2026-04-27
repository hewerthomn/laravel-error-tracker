<?php

namespace Hewerthomn\ErrorTracker\Support;

class StackTracePresenter
{
    public function __construct(
        protected StackFrameClassifier $classifier,
        protected SourceContextReader $sourceContextReader,
    ) {}

    /**
     * @return array{frames: array<int, array<string, mixed>>, first_project_frame: array<string, mixed>|null, culprit_frame: array<string, mixed>|null, throwing_frame: array<string, mixed>|null, has_frames: bool}
     */
    public function present(mixed $trace): array
    {
        $frames = $this->normalizeTrace($trace);
        $presentedFrames = [];
        $pendingGroup = [];
        $firstProjectFrame = null;
        $culpritFrame = null;
        $throwingFrame = null;
        $smartGrouping = (bool) config('error-tracker.stacktrace.smart_grouping', true);

        foreach ($frames as $index => $frame) {
            $presented = $this->presentFrame($frame, $index);
            $culpritFrame ??= $presented['is_culprit'] ? $presented : null;
            $throwingFrame ??= $presented['is_throwing_frame'] ? $presented : null;

            if ($presented['classification'] === 'project' || $presented['is_culprit']) {
                $this->flushGroup($presentedFrames, $pendingGroup);
                $presentedFrames[] = $presented;
                $firstProjectFrame ??= $presented['classification'] === 'project' ? $presented : null;

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
            'culprit_frame' => $culpritFrame ?? $firstProjectFrame,
            'throwing_frame' => $throwingFrame,
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
        $classification = $this->frameClassification($frame);
        $file = $this->stringValue($frame['file'] ?? null);
        $displayFile = $this->classifier->relativeFile($file);
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
            'is_throwing_frame' => (bool) ($frame['is_throwing_frame'] ?? false),
            'is_culprit' => (bool) ($frame['is_culprit'] ?? false),
            'file' => $displayFile,
            'relative_file' => $displayFile,
            'line' => $line,
            'class' => $class,
            'function' => $function,
            'callable' => $callable !== '' ? $callable : 'unknown',
            'source_context' => $classification === 'project'
                ? ($frame['source_context'] ?? $this->sourceContextReader->read(['file' => $file, 'line' => $line]))
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

    protected function frameClassification(array $frame): string
    {
        $classification = $this->stringValue($frame['classification'] ?? null);

        if (in_array($classification, ['project', 'vendor', 'framework', 'internal', 'unknown'], true)) {
            return $classification;
        }

        return $this->classifier->classify($frame);
    }
}
