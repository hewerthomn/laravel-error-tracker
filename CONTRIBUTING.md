# Contributing

## Development Setup

Install dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
```

Check formatting:

```bash
composer format:test
```

Format the codebase:

```bash
composer format
```

Run static analysis:

```bash
composer analyse
```

## Pull Request Checklist

* Tests pass.
* Pint passes.
* PHPStan passes.
* README updated when behavior changes.
* New behavior includes tests when possible.

## Code Style

This project uses Laravel Pint to keep code style consistent. Run `composer format` before submitting code changes, and use `composer format:test` to verify formatting in CI-style checks.
