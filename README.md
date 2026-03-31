# Marwa Envelop

Small, framework-agnostic message envelopes for PHP applications that need a consistent payload format across Kafka, WebSocket, MQTT, queues, and internal event pipelines.

[![CI](https://github.com/memran/marwa-envelop/actions/workflows/ci.yml/badge.svg)](https://github.com/memran/marwa-envelop/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/memran/marwa-envelop.svg)](https://packagist.org/packages/memran/marwa-envelop)
[![PHP Version](https://img.shields.io/packagist/dependency-v/memran/marwa-envelop/php)](https://packagist.org/packages/memran/marwa-envelop)
[![Packagist Downloads](https://img.shields.io/packagist/dt/memran/marwa-envelop.svg)](https://packagist.org/packages/memran/marwa-envelop)
[![License](https://img.shields.io/github/license/memran/marwa-envelop.svg)](LICENSE)

Marwa Envelop gives you an immutable envelope object, a fluent builder, and a codec for safe serialization, compression, and signature verification. It is designed for libraries and services that want predictable message structure without framework lock-in.

## Features

- Immutable message envelopes with a fluent builder API
- JSON serialization with explicit error handling
- Optional HMAC signing and signature verification
- Optional gzip transport encoding
- Built-in payload size limits for wire and decoded content
- File attachments and validated `http`/`https` resource links
- Lightweight metadata validation for message types and identifiers
- PHPUnit, PHPStan, php-cs-fixer, and GitHub Actions included

## Requirements

- PHP 8.2 or newer
- Composer for dependency management

## Installation

```bash
composer require memran/marwa-envelop
```

## Quick Start

```php
<?php

use Marwa\Envelop\Codec;
use Marwa\Envelop\Envelop;
use Marwa\Envelop\EnvelopBuilder;

$message = EnvelopBuilder::start()
    ->type('chat.message')
    ->sender('user:123')
    ->receiver('user:456')
    ->header('x-room', 'support')
    ->body(['text' => 'Hello world'])
    ->ttl(60)
    ->sign('shared-secret')
    ->build();

$wire = Codec::encode($message, ['compression' => Codec::COMPRESSION_GZIP]);
$decoded = Codec::decode($wire, [
    'compression' => Codec::COMPRESSION_GZIP,
    'verifyWithSecret' => 'shared-secret',
    'signatureRequired' => true,
]);

assert($decoded instanceof Envelop);
```

For untrusted senders, keep or tighten the built-in size limits:

```php
$decoded = Codec::decode($wire, [
    'compression' => Codec::COMPRESSION_GZIP,
    'maxWireBytes' => 512 * 1024,
    'maxDecodedBytes' => 2 * 1024 * 1024,
]);
```

## Core Concepts

- `Envelop`: immutable message value object
- `EnvelopBuilder`: fluent API for constructing messages safely
- `Codec`: encoding, gzip compression, and optional signature verification
- `Util`: internal helpers for UUID and MIME detection

## Usage Examples

### Plain text or JSON payloads

```php
$message = EnvelopBuilder::start()
    ->type('chat.message')
    ->body('Hello')
    ->build();
```

```php
$message = EnvelopBuilder::start()
    ->type('job.created')
    ->body(['id' => 42, 'priority' => 'high'])
    ->build();
```

### File attachments

```php
$message = EnvelopBuilder::start()
    ->type('chat.file')
    ->attach('/absolute/path/to/invoice.pdf')
    ->build();
```

Attachments are base64 encoded and include the original filename in the `x-filename` header.

### Remote file links

```php
$message = EnvelopBuilder::start()
    ->type('file.link')
    ->link('https://example.com/report.pdf', [
        'name' => 'Quarterly Report',
        'size' => '2MB',
    ])
    ->build();
```

Linked resource URLs must be absolute `http` or `https` URLs.

## Validation and Safe Defaults

- `type()` is required before `build()`
- message types must use letters, numbers, dots, underscores, or hyphens
- required IDs must be non-empty; optional IDs reject blank or control-character values
- negative TTL values are rejected
- malformed JSON and invalid timestamps throw `InvalidArgumentException`
- unreadable files passed to `attach()` throw `RuntimeException`
- `Codec` enforces default wire and decoded payload size limits
- signatures are only considered valid when the payload matches exactly

This package does not sanitize or authorize application data for you. Treat decoded payloads, file paths, and shared secrets as untrusted inputs at the application boundary.

## Project Structure

```text
src/
  Codec.php
  Envelop.php
  EnvelopBuilder.php
  Util.php
tests/
.github/workflows/ci.yml
```

## Development

Install dependencies:

```bash
composer install
```

Common commands:

```bash
composer test
composer test:coverage
composer analyse
composer lint
composer fix
composer ci
```

## Testing and Static Analysis

- PHPUnit 10 covers serialization, signatures, TTL expiry, compression, and invalid-input regressions
- PHPStan 2 checks `src/` and `tests/`
- php-cs-fixer enforces PSR-12-oriented formatting

## CI and Releases

GitHub Actions runs linting, static analysis, and tests on pushes and pull requests. Tag releases after `composer ci` passes and the README reflects any public API changes.

## Contributing

See [AGENTS.md](AGENTS.md) for repository workflow, coding expectations, and pull request guidance.

## License

MIT
