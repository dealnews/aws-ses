<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Exception;

/**
 * Thrown when AWS SES rejects or fails to process a SendEmail
 * request. Wraps the underlying AWS SDK exception, which is
 * available via getPrevious().
 */
class SendException extends \RuntimeException implements AwsSesExceptionInterface {
}
