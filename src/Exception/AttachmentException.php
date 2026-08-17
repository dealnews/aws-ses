<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Exception;

/**
 * Thrown when an attachment cannot be built, such as when a file
 * path does not exist or cannot be read.
 */
class AttachmentException extends ValidationException {
}
