# Security Policy

## Supported Versions

This package is in early development. Security fixes target the latest released version.

## Reporting a Vulnerability

Please report security vulnerabilities privately through a GitHub Security Advisory or by contacting the maintainer directly.

Do not disclose vulnerabilities publicly before a fix is available.

## Sensitive Data

This package stores exception context, sanitized headers, stack traces, and optional user feedback. Applications should review the redaction configuration before enabling the package in production.
