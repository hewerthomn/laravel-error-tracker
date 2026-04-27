<?php

namespace Hewerthomn\ErrorTracker\Support\Diagnostics;

class SecretMasker
{
    /**
     * @var array<int, string>
     */
    protected array $sensitiveFragments = [
        'slack_webhook_url',
        'webhook',
        'mail_to',
        'token',
        'secret',
        'password',
        'authorization',
        'cookie',
        'x-api-key',
    ];

    public function status(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value) !== '' ? 'configured' : 'not configured';
        }

        if (is_array($value)) {
            return $value !== [] ? 'configured' : 'not configured';
        }

        return $value === null || $value === false ? 'not configured' : 'configured';
    }

    public function mask(string $key, mixed $value): mixed
    {
        if ($this->isSensitiveKey($key)) {
            return $this->status($value);
        }

        return $value;
    }

    public function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach ($this->sensitiveFragments as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
