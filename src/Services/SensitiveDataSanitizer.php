<?php

namespace Hewerthomn\ErrorTracker\Services;

class SensitiveDataSanitizer
{
    public function sanitizeContext(array $context): array
    {
        return $this->sanitizeArray(
            $context,
            config('error-tracker.redaction.request_fields', [])
        );
    }

    public function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        $redactedHeaders = array_map(
            static fn (string $header) => strtolower($header),
            config('error-tracker.redaction.headers', [])
        );

        foreach ($headers as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $redactedHeaders, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    public function sanitizeArray(array $data, array $sensitiveKeys = []): array
    {
        $sanitized = [];
        $normalizedSensitiveKeys = array_map('strtolower', $sensitiveKeys);

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $normalizedSensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $sensitiveKeys);

                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    protected function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return sprintf('[object:%s]', $value::class);
        }

        return $value;
    }
}
