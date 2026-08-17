<?php

declare(strict_types=1);

namespace DealNews\AwsSes;

use DealNews\AwsSes\Exception\AttachmentException;

/**
 * A file attachment to be included on an email, made up of its
 * filename, raw content, and MIME content type.
 */
class Attachment {

    /**
     * MIME content type used when none is given to the constructor.
     *
     * @var string
     */
    public const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    /**
     * Filename shown to the recipient.
     *
     * @var string
     */
    protected string $filename;

    /**
     * Raw binary content of the attachment.
     *
     * @var string
     */
    protected string $content;

    /**
     * MIME content type of the attachment.
     *
     * @var string
     */
    protected string $content_type;

    /**
     * @param string $filename     Filename shown to the recipient.
     * @param string $content      Raw binary content of the attachment.
     * @param string $content_type MIME content type of the attachment.
     */
    public function __construct(
        string $filename,
        string $content,
        string $content_type = self::DEFAULT_CONTENT_TYPE
    ) {
        $this->filename     = $filename;
        $this->content      = $content;
        $this->content_type = $content_type;
    }

    /**
     * @return string The filename shown to the recipient.
     */
    public function getFilename(): string {
        return $this->filename;
    }

    /**
     * @return string The raw binary content of the attachment.
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * @return string The MIME content type of the attachment.
     */
    public function getContentType(): string {
        return $this->content_type;
    }

    /**
     * Builds an Attachment by reading a file from disk.
     *
     * @param string      $path         Path to the file to attach.
     * @param string|null $filename     Filename to show the recipient.
     *                                  Defaults to the source file's
     *                                  basename.
     * @param string|null $content_type MIME content type. Detected from
     *                                  the file when not given.
     *
     * @throws AttachmentException When the file cannot be read.
     */
    public static function fromPath(
        string $path,
        ?string $filename = null,
        ?string $content_type = null
    ): self {
        if (!is_readable($path) || !is_file($path)) {
            throw new AttachmentException(
                sprintf('Attachment file "%s" does not exist or is not readable.', $path)
            );
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new AttachmentException(
                sprintf('Failed to read attachment file "%s".', $path)
            );
        }

        if ($content_type === null) {
            $finfo        = new \finfo(FILEINFO_MIME_TYPE);
            $detected     = $finfo->file($path);
            $content_type = (string) ($detected ?: self::DEFAULT_CONTENT_TYPE);
        }

        return new self($filename ?? basename($path), $content, $content_type);
    }
}
