<?php

namespace Hewerthomn\ErrorTracker\Support;

class StackFrameClassifier
{
    public function classify(array $frame): string
    {
        $file = $this->stringValue($frame['file'] ?? null);
        $class = $this->stringValue($frame['class'] ?? null);
        $function = $this->stringValue($frame['function'] ?? null);

        if ($file !== null && $this->isWithinAnyPath($file, $this->projectPaths())) {
            return 'project';
        }

        if ($class !== null && $this->startsWithAny($class, $this->projectNamespaces())) {
            return 'project';
        }

        if ($file !== null && $this->isFrameworkPath($file)) {
            return 'framework';
        }

        if ($file !== null && $this->isVendorPath($file)) {
            return 'vendor';
        }

        if ($file !== null && $this->isWithinAnyPath($file, $this->nonProjectPaths())) {
            return 'vendor';
        }

        if ($file === null) {
            return ($class !== null || $function !== null) ? 'internal' : 'unknown';
        }

        return 'unknown';
    }

    public function isProjectFile(?string $file): bool
    {
        return $file !== null && $this->isWithinAnyPath($file, $this->projectPaths());
    }

    public function relativeFile(?string $file): ?string
    {
        if ($file === null || $file === '') {
            return null;
        }

        $normalized = $this->normalizePath($file);
        $basePath = $this->normalizePath(base_path());

        if ($this->pathEqualsOrIsInside($normalized, $basePath)) {
            return ltrim(substr($normalized, strlen($basePath)), '/');
        }

        foreach ($this->projectPaths() as $path) {
            $projectPath = $this->normalizePath($path);

            if (! $this->pathEqualsOrIsInside($normalized, $projectPath)) {
                continue;
            }

            $prefix = basename($projectPath);
            $suffix = ltrim(substr($normalized, strlen($projectPath)), '/');

            return $suffix === '' ? $prefix : $prefix.'/'.$suffix;
        }

        return $normalized;
    }

    public function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        if (strlen($normalized) > 1) {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    protected function projectPaths(): array
    {
        return $this->configuredPaths('error-tracker.stacktrace.project_paths');
    }

    /**
     * @return array<int, string>
     */
    protected function nonProjectPaths(): array
    {
        return $this->configuredPaths('error-tracker.stacktrace.non_project_paths');
    }

    /**
     * @return array<int, string>
     */
    protected function projectNamespaces(): array
    {
        $namespaces = config('error-tracker.stacktrace.project_namespaces', ['App\\', 'Database\\']);

        if (! is_array($namespaces)) {
            return [];
        }

        return collect($namespaces)
            ->filter(fn ($namespace) => is_string($namespace) && $namespace !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function configuredPaths(string $key): array
    {
        $paths = config($key, []);

        if (! is_array($paths)) {
            return [];
        }

        return collect($paths)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->flatMap(function (string $path): array {
                $normalized = $this->normalizePath($path);
                $realPath = realpath($path);

                if ($realPath === false) {
                    return [$normalized];
                }

                return [$normalized, $this->normalizePath($realPath)];
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function isWithinAnyPath(string $file, array $paths): bool
    {
        $normalizedFile = $this->normalizePath($file);

        foreach ($paths as $path) {
            if ($this->pathEqualsOrIsInside($normalizedFile, $this->normalizePath($path))) {
                return true;
            }
        }

        return false;
    }

    protected function isFrameworkPath(string $file): bool
    {
        $normalized = $this->normalizePath($file);

        return str_contains($normalized, '/storage/framework/')
            || str_ends_with($normalized, '/storage/framework')
            || str_contains($normalized, '/bootstrap/cache/')
            || str_ends_with($normalized, '/bootstrap/cache');
    }

    protected function isVendorPath(string $file): bool
    {
        $normalized = $this->normalizePath($file);

        return str_contains($normalized, '/vendor/')
            || str_ends_with($normalized, '/vendor');
    }

    protected function pathEqualsOrIsInside(string $file, string $path): bool
    {
        $file = strtolower($this->normalizePath($file));
        $path = strtolower($this->normalizePath($path));

        return $file === $path || str_starts_with($file, $path.'/');
    }

    /**
     * @param  array<int, string>  $prefixes
     */
    protected function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
