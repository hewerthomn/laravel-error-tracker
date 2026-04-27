<?php

namespace Hewerthomn\ErrorTracker\Support\StackTrace;

class PathNormalizer
{
    public function normalize(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $mode = (string) config('error-tracker.stacktrace.path_display', 'relative');
        $storeAbsolutePaths = (bool) config('error-tracker.stacktrace.store_absolute_paths', false);

        if ($mode === 'basename') {
            return basename($this->normalizeSeparators($path));
        }

        if ($mode === 'absolute' && $storeAbsolutePaths) {
            return $this->normalizeSeparators($path);
        }

        return $this->toRelative($path);
    }

    public function toRelative(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $normalized = $this->normalizeSegments($this->normalizeSeparators($path));
        $basePath = $this->normalizeSegments($this->normalizeSeparators(base_path()));
        $candidates = $this->pathCandidates($normalized);
        $baseCandidates = $this->pathCandidates($basePath);

        foreach ($candidates as $candidate) {
            foreach ($baseCandidates as $baseCandidate) {
                if (! $this->pathEqualsOrIsInside($candidate, $baseCandidate)) {
                    continue;
                }

                $relative = ltrim(substr($candidate, strlen($baseCandidate)), '/');

                return $relative === '' ? basename($baseCandidate) : $relative;
            }
        }

        if ($this->isAbsolutePath($normalized)) {
            return basename($normalized);
        }

        return ltrim($normalized, '/');
    }

    public function toAbsoluteFromRelative(?string $relativePath): ?string
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return null;
        }

        $normalized = $this->normalizeSegments($this->normalizeSeparators($relativePath));

        if ($this->isAbsolutePath($normalized)) {
            return $normalized;
        }

        $basePath = $this->normalizeSegments($this->normalizeSeparators(base_path()));
        $absolute = $this->normalizeSegments($basePath.'/'.ltrim($normalized, '/'));

        if (! $this->pathEqualsOrIsInside($absolute, $basePath)) {
            return null;
        }

        return $absolute;
    }

    /**
     * @param  array<int, string>  $allowedPaths
     */
    public function isInsideAllowedPaths(string $absolutePath, array $allowedPaths): bool
    {
        $pathCandidates = $this->pathCandidates($absolutePath);

        foreach ($allowedPaths as $allowedPath) {
            if ($allowedPath === '') {
                continue;
            }

            $realAllowedPath = realpath($allowedPath);
            $normalizedAllowedPath = $this->normalizeSegments($this->normalizeSeparators(
                $realAllowedPath === false ? $allowedPath : $realAllowedPath
            ));

            foreach ($pathCandidates as $pathCandidate) {
                if ($this->pathEqualsOrIsInside($pathCandidate, $normalizedAllowedPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function normalizeSeparators(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        if (strlen($normalized) > 1) {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized;
    }

    protected function normalizeSegments(string $path): string
    {
        $path = $this->normalizeSeparators($path);
        $prefix = '';

        if (preg_match('/^[A-Za-z]:\//', $path) === 1) {
            $prefix = substr($path, 0, 3);
            $path = substr($path, 3);
        } elseif (str_starts_with($path, '/')) {
            $prefix = '/';
            $path = ltrim($path, '/');
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($segments !== [] && end($segments) !== '..') {
                    array_pop($segments);

                    continue;
                }

                if ($prefix === '') {
                    $segments[] = $segment;
                }

                continue;
            }

            $segments[] = $segment;
        }

        return $prefix.implode('/', $segments);
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path) === 1;
    }

    /**
     * @return array<int, string>
     */
    protected function pathCandidates(string $path): array
    {
        $normalized = $this->normalizeSegments($this->normalizeSeparators($path));
        $candidates = [$normalized];
        $realPath = realpath($normalized);

        if ($realPath !== false) {
            $candidates[] = $this->normalizeSegments($this->normalizeSeparators($realPath));
        }

        return array_values(array_unique($candidates));
    }

    protected function pathEqualsOrIsInside(string $path, string $directory): bool
    {
        $path = strtolower($this->normalizeSegments($path));
        $directory = strtolower($this->normalizeSegments($directory));

        return $path === $directory || str_starts_with($path, rtrim($directory, '/').'/');
    }
}
