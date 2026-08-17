# dealnews/aws-ses

## Project Overview

A fluent PHP library that wraps the AWS SDK's `Aws\SesV2\SesV2Client` for
sending email. Consumers build a `Message` with a chainable API (from, to,
subject, body, attachments, headers), pass it to `Mailer::send()`, and get
back a typed `SendResult`. Requires PHP `^8.2` (see `composer.json`).

## Key Commands

- Install: `composer install`
- Lint: `composer lint` (runs `php-parallel-lint` over `src/` and `tests/`)
- Fix: `composer fix` (runs `php-cs-fixer` using `.php-cs-fixer.dist.php`)
- Test all: `composer test` (runs lint, then PHPUnit with coverage)
- Test single file: `composer test -- tests/MessageTest.php`

## Project Structure

```
src/
  Message.php               Fluent builder for an outgoing email
  Address.php                Value object: validated email + optional name
  Attachment.php              Value object: filename + content + content-type
  Header.php                   Value object: custom header name/value
  Mailer.php                    Wraps SesV2Client; ->send(Message): SendResult
  EmailContentBuilder.php        Builds the SendEmail "Content" parameter
  SendResult.php                  Value object: message id, status, request id
  Exception/                      Validation vs. send-time exception hierarchy

tests/
  *Test.php     One test class per src/ class, PHPUnit
  fixtures/     Static files used by tests (e.g. a sample attachment)
```

## Code Style

- 1TBS bracing style
- snake_case variables
- Protected visibility by default
- Single return point preference
- Class-based API (no bare functions)
- Dependency injection is handled by optional parameters passed to class constructors (unless specified, otherwise)
- Complete PHPDoc coverage

## Non-Obvious Patterns

- **Attachments and custom headers do not use raw/MIME content.** SES v2's
  `Content.Simple` (the shape modeled by the installed `aws/aws-sdk-php`
  version) natively supports `Attachments` and `Headers` fields, so
  `EmailContentBuilder` always builds `Content.Simple` — it never builds
  `Content.Raw`. Don't reach for a MIME-building library (e.g.
  `symfony/mime`) to add attachment support; that path was evaluated and
  intentionally dropped in favor of the simpler native-Simple-attachments
  approach.
- **No class in `src/` is `final`.** This is deliberate, not an oversight —
  it's so consumers can mock or subclass any of them (`Message`, `Mailer`,
  `EmailContentBuilder`, the value objects, etc.) in their own test suites.
  Don't add `final` back without checking with a maintainer first.
- **`Mailer` takes a pre-configured `SesV2Client` via constructor
  injection** rather than building one internally. Tests exploit this by
  injecting a client configured with `Aws\MockHandler` (see
  `tests/MailerTest.php`) instead of mocking `Mailer` itself — no real AWS
  calls or credentials are needed to run the suite.

## Workflow

- All tests must pass after code changes, with no PHPUnit deprecation
  warnings — run `composer test`.
- Run `composer fix` at the end of code changes to apply code style.

## Key Files

- `src/Message.php` — the main entry point consumers build up before
  sending; also owns required-field validation (`validate()`).
- `src/Mailer.php` — the only class that talks to AWS; validates a
  `Message`, builds the SendEmail request, and wraps any AWS SDK exception
  in `Exception\SendException`.
- `src/EmailContentBuilder.php` — translates a `Message` into the SES v2
  `Content` request shape; see the non-obvious pattern above before
  changing how attachments/headers are built.
- `.php-cs-fixer.dist.php` — code style rules enforced by `composer fix`.
- `README.md` — user-facing usage docs and full API reference.
