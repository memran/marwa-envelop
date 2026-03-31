# Repository Guidelines

## Project Structure & Module Organization
This repository is a small PHP library distributed through Composer. Production code lives in `src/` under the `Marwa\\Envelop\\` PSR-4 namespace:

- `src/Envelop.php`: immutable message model
- `src/EnvelopBuilder.php`: fluent builder API
- `src/Codec.php`: encode/decode and compression helpers
- `src/Util.php`: UUID and MIME utilities

Keep new library classes in `src/` and match filenames to class names. Add tests in a top-level `tests/` directory when extending behavior.

## Build, Test, and Development Commands
Use Composer for local development:

- `composer install`: install runtime and dev dependencies
- `composer dump-autoload`: refresh PSR-4 autoload metadata after adding classes
- `vendor/bin/phpunit`: run the PHPUnit 10 test suite

There is no build step or framework bootstrap in this package. Use the examples in `README.md` for quick manual validation.

## Coding Style & Naming Conventions
Target PHP `^8.2` as defined in `composer.json`. Follow the existing style in `src/`:

- `declare(strict_types=1);` at the top of each PHP file
- 4-space indentation, braces on the next line for classes and methods
- `final` classes where extension is not intended
- PascalCase for classes, camelCase for methods, descriptive dotted strings for message types such as `chat.message`

No formatter is configured in the repo, so keep changes PSR-12-aligned and consistent with the surrounding file.

## Testing Guidelines
PHPUnit is the declared test framework. Place tests in `tests/` and name files after the unit under test, for example `tests/EnvelopBuilderTest.php`. Cover JSON serialization, signature verification, TTL expiry, and codec compression branches for any behavior change. Run `vendor/bin/phpunit` before opening a PR.

## Commit & Pull Request Guidelines
Recent commits use short, imperative subjects such as `Add LICENSE` and `Update readme file`. Keep commit messages concise, present tense, and focused on one change. For pull requests, include:

- a clear summary of the behavior change
- linked issue or context when applicable
- test evidence (`vendor/bin/phpunit`) or a short manual verification note
- README updates when public API or usage changes

## Security & Configuration Notes
Do not commit secrets or sample shared keys used for HMAC signing. Treat attached files and decoded payloads as untrusted input in tests and examples.
