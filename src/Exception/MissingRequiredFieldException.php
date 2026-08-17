<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Exception;

/**
 * Thrown when a message is sent without a required field set,
 * such as a from address, a recipient, a subject, or a body.
 */
class MissingRequiredFieldException extends ValidationException {
}
