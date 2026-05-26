<?php

namespace Hewerthomn\ErrorTracker\Exceptions;

use RuntimeException;

class ExternalIssueTrackerException extends RuntimeException
{
    public static function configuration(string $message): self
    {
        return new self($message);
    }

    public static function githubStatus(int $status): self
    {
        return new self(match ($status) {
            401 => 'GitHub token is invalid or expired.',
            403 => 'GitHub token does not have permission to access this repository.',
            404 => 'GitHub repository or issue was not found, or the token cannot access it.',
            410 => 'GitHub issues are disabled or unavailable for this repository.',
            422 => 'GitHub rejected the issue payload. Check labels, assignees, and repository settings.',
            default => 'GitHub issue integration failed. Try again later.',
        });
    }
}
