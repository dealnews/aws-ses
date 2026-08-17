# dealnews/aws-ses

A fluent PHP wrapper around the AWS SDK's SES v2 client for sending email —
build a message with a chainable API, send it, and get back a typed result.
No raw AWS SDK arrays to hand-assemble, no MIME to build by hand.

## Features

- **Fluent message builder** — chain `from()`, `to()`, `subject()`, `html()`,
  `attach()`, and more to build up an email.
- **Attachments, without MIME wrangling** — attach files (raw bytes or by
  path) and the library sends them through SES v2's native attachment
  support, no manual multipart/MIME construction required.
- **Custom headers** — add arbitrary headers (e.g. `X-` headers) to outgoing
  mail.
- **Fails fast** — invalid email addresses and missing required fields
  (from, recipient, subject, body) throw immediately, before anything is
  sent to AWS.
- **Typed exceptions** — a single interface (`AwsSesExceptionInterface`) to
  catch anything this library throws, with distinct exception types for
  build-time validation errors vs. send-time AWS failures.
- **Typed result object** — `send()` returns a `SendResult` value object
  (message ID, HTTP status, request ID), not a raw AWS `Result`/array.
- **Bring your own client** — you construct and configure the
  `Aws\SesV2\SesV2Client` (region, credentials, etc.); this library just
  builds the request and sends it, which also makes it trivial to test
  against with a mocked client.

## Installation

```bash
composer require dealnews/aws-ses
```

Requires PHP 8.2+ and `aws/aws-sdk-php` (installed automatically as a
dependency).

You'll also need AWS credentials configured wherever this runs (environment
variables, an IAM instance/task role, a shared credentials file, etc.) with
permission to call `ses:SendEmail`, and a verified sending identity
(domain or email address) in SES.

## Quick Start

```php
use Aws\SesV2\SesV2Client;
use DealNews\AwsSes\Mailer;
use DealNews\AwsSes\Message;

$client = new SesV2Client([
    'region'  => 'us-east-1',
    'version' => '2019-09-27',
]);

$mailer = new Mailer($client);

$result = $mailer->send(
    (new Message())
        ->from('no-reply@dealnews.com', 'DealNews')
        ->to('customer@example.com')
        ->subject('Your order has shipped')
        ->html('<p>Thanks for your order!</p>')
        ->text('Thanks for your order!')
);

echo $result->getMessageId();
```

## Usage Examples

### Multiple recipients

Call `to()`, `cc()`, `bcc()`, or `replyTo()` once per address — each call
appends a recipient rather than replacing the list:

```php
$message = (new Message())
    ->from('no-reply@dealnews.com', 'DealNews')
    ->to('customer@example.com')
    ->to('customer-alt@example.com', 'Alt Contact')
    ->cc('audit@dealnews.com')
    ->bcc('archive@dealnews.com')
    ->replyTo('support@dealnews.com')
    ->subject('Your order has shipped')
    ->text('Thanks for your order!');

$mailer->send($message);
```

### Attachments

Attach raw bytes directly, or read a file from disk:

```php
$message = (new Message())
    ->from('billing@dealnews.com')
    ->to('customer@example.com')
    ->subject('Your invoice')
    ->text('See the attached invoice.')
    ->attach($pdf_bytes, 'invoice.pdf', 'application/pdf')
    ->attachFromPath('/tmp/receipt.png'); // filename/content-type auto-detected

$mailer->send($message);
```

`attachFromPath()` defaults the recipient-facing filename to the source
file's basename and detects the content type from the file itself; both can
be overridden with the second and third arguments.

### Custom headers and configuration sets

```php
$message = (new Message())
    ->from('no-reply@dealnews.com')
    ->to('customer@example.com')
    ->subject('Your order has shipped')
    ->text('Thanks for your order!')
    ->header('X-DealNews-Category', 'transactional')
    ->configurationSetName('transactional-emails');

$mailer->send($message);
```

### Handling errors

```php
use DealNews\AwsSes\Exception\AwsSesExceptionInterface;
use DealNews\AwsSes\Exception\MissingRequiredFieldException;
use DealNews\AwsSes\Exception\SendException;

try {
    $mailer->send($message);
} catch (MissingRequiredFieldException $e) {
    // The message itself was incomplete (no from/recipient/subject/body).
    // AWS was never contacted.
} catch (SendException $e) {
    // AWS rejected or failed to process the request.
    // The underlying AWS SDK exception is available via getPrevious().
    $aws_exception = $e->getPrevious();
} catch (AwsSesExceptionInterface $e) {
    // Catches anything else this library throws (e.g. an invalid
    // email address passed to from()/to()/cc()/bcc()/replyTo()).
}
```

## API Overview

### `DealNews\AwsSes\Message`

The fluent builder used to describe an email. All setters return `$this`,
so calls chain. Getters (`getFrom()`, `getTo()`, etc.) are also available if
you need to inspect a message you've built.

| Method | Description |
| --- | --- |
| `from(string $email, ?string $name = null)` | Sets the sender address. |
| `to(string $email, ?string $name = null)` | Appends a "to" recipient. Call once per recipient. |
| `cc(string $email, ?string $name = null)` | Appends a "cc" recipient. |
| `bcc(string $email, ?string $name = null)` | Appends a "bcc" recipient. |
| `replyTo(string $email, ?string $name = null)` | Appends a "reply-to" address. |
| `subject(string $subject)` | Sets the subject line. |
| `text(string $body)` | Sets the plain text body. |
| `html(string $body)` | Sets the HTML body. |
| `attach(string $content, string $filename, string $content_type = 'application/octet-stream')` | Appends an attachment from raw content. |
| `attachFromPath(string $path, ?string $filename = null, ?string $content_type = null)` | Appends an attachment read from disk. |
| `header(string $name, string $value)` | Adds a custom email header. |
| `configurationSetName(string $name)` | Sets the SES configuration set to send under. |
| `charset(string $charset)` | Sets the charset for the subject/body (default `UTF-8`). |
| `hasAttachments(): bool` | Whether one or more attachments have been added. |
| `validate(): void` | Throws `MissingRequiredFieldException` if a required field (from, at least one recipient, subject, a text or HTML body) is missing. Called automatically by `Mailer::send()`. |

A `Message` requires, at minimum, a `from`, at least one recipient
(`to`/`cc`/`bcc`), a `subject`, and a `text` and/or `html` body.

### `DealNews\AwsSes\Mailer`

```php
public function __construct(SesV2Client $ses_client, ?EmailContentBuilder $content_builder = null)
public function send(Message $message): SendResult
```

Wraps a configured `Aws\SesV2\SesV2Client`. `send()` validates the message,
builds the SES v2 `SendEmail` request (envelope from `Message`, `Content`
from `EmailContentBuilder`), and returns a `SendResult`. Any exception from
the AWS SDK is caught and rethrown as `SendException`, with the original
exception available via `getPrevious()`.

The second constructor argument is mainly useful for testing or advanced
customization — the default `EmailContentBuilder` is used if you don't pass
one.

### `DealNews\AwsSes\EmailContentBuilder`

```php
public function build(Message $message): array
```

Builds the `Content` parameter of the SES v2 `SendEmail` request from a
`Message`. Always builds `Content.Simple` — subject, body, and (when
present) `Headers` and `Attachments` — since SES v2's Simple content
natively supports both. You generally won't call this directly; `Mailer`
does it for you.

### `DealNews\AwsSes\SendResult`

```php
public function getMessageId(): string
public function getHttpStatusCode(): int
public function getRequestId(): string
```

Returned by `Mailer::send()` on success.

### `DealNews\AwsSes\Address`, `Header`, `Attachment`

Small value objects used internally and returned from `Message`'s getters.

- `Address::__construct(string $email, ?string $name = null)` validates the
  email immediately (throws `InvalidAddressException` if invalid), and
  exposes `getEmail()`, `getName()`, and `toString()` (renders as
  `"Name <email>"` or bare `"email"`).
- `Header::__construct(string $name, string $value)` exposes `getName()`
  and `getValue()`.
- `Attachment::__construct(string $filename, string $content, string $content_type = 'application/octet-stream')`
  exposes `getFilename()`, `getContent()`, `getContentType()`, and the
  static factory `Attachment::fromPath(string $path, ?string $filename = null, ?string $content_type = null)`.

### Exceptions (`DealNews\AwsSes\Exception`)

| Exception | Thrown when |
| --- | --- |
| `AwsSesExceptionInterface` | *(marker interface, not thrown directly)* — implemented by every exception below, so you can catch any of them at once. |
| `ValidationException` | Base class for all build-time validation errors below. Extends `\InvalidArgumentException`. |
| `InvalidAddressException` | An email passed to `from()`/`to()`/`cc()`/`bcc()`/`replyTo()` fails validation. |
| `MissingRequiredFieldException` | `Message::validate()` finds a missing required field. |
| `AttachmentException` | `Attachment::fromPath()` can't read the given file. |
| `SendException` | The AWS SDK throws while calling `SendEmail`. Extends `\RuntimeException`; the original exception is available via `getPrevious()`. |

## Testing

Run the test suite:

```bash
composer test
```

This runs `parallel-lint` followed by PHPUnit (with coverage). Individual
tools are also available directly:

```bash
composer lint   # php-parallel-lint over src/ and tests/
vendor/bin/phpunit
```

Tests never make real AWS calls — `Aws\SesV2\SesV2Client` is constructed
with an `Aws\MockHandler` that intercepts requests and returns canned
responses. If you're testing code that uses this library, the same
approach works well:

```php
use Aws\MockHandler;
use Aws\Result;
use Aws\SesV2\SesV2Client;

$mock_handler = new MockHandler();
$mock_handler->append(new Result(['MessageId' => 'test-message-id']));

$client = new SesV2Client([
    'region'      => 'us-east-1',
    'version'     => '2019-09-27',
    'handler'     => $mock_handler,
    'credentials' => ['key' => 'test', 'secret' => 'test'],
]);

$mailer = new Mailer($client);
```

To assert on the request that would have been sent, append a closure
instead of a `Result` — it receives the `Aws\CommandInterface` and must
return the `Result` to respond with:

```php
$mock_handler->append(function ($command) {
    // $command['Content']['Simple']['Attachments'], etc.
    return new Result(['MessageId' => 'test-message-id']);
});
```

## Code Style

This repo uses `php-cs-fixer` (config in `.php-cs-fixer.dist.php`):

```bash
composer fix   # auto-fix code style
```

## License

BSD-3-Clause. See the `license` field in `composer.json`.
