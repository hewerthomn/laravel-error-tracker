<?php

use Hewerthomn\ErrorTracker\Services\SensitiveDataSanitizer;

it('redacts sensitive headers and context fields', function () {
    $sanitizer = app(SensitiveDataSanitizer::class);

    $headers = $sanitizer->sanitizeHeaders([
        'Authorization' => 'Bearer secret-token',
        'Cookie' => 'session=123',
        'Accept' => 'application/json',
    ]);

    $context = $sanitizer->sanitizeContext([
        'password' => 'secret-password',
        'token' => 'plain-token',
        'profile' => [
            'name' => 'Jane',
            'password_confirmation' => 'secret-password',
        ],
        'count' => 10,
    ]);

    expect($headers['Authorization'])->toBe('[REDACTED]')
        ->and($headers['Cookie'])->toBe('[REDACTED]')
        ->and($headers['Accept'])->toBe('application/json')
        ->and($context['password'])->toBe('[REDACTED]')
        ->and($context['token'])->toBe('[REDACTED]')
        ->and($context['profile']['password_confirmation'])->toBe('[REDACTED]')
        ->and($context['profile']['name'])->toBe('Jane')
        ->and($context['count'])->toBe(10);
});
