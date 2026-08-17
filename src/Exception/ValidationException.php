<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Exception;

/**
 * Thrown when a message or one of its parts fails to validate
 * before being sent.
 */
class ValidationException extends \InvalidArgumentException implements
    AwsSesExceptionInterface {
}
