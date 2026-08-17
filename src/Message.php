<?php

declare(strict_types=1);

namespace DealNews\AwsSes;

use DealNews\AwsSes\Exception\MissingRequiredFieldException;

/**
 * A fluent builder describing an email to be sent through
 * DealNews\AwsSes\Mailer. Accumulates recipients, subject, body,
 * attachments, and headers, and validates itself before send.
 */
class Message {

    /**
     * Charset used for the subject and body when none is set.
     *
     * @var string
     */
    public const DEFAULT_CHARSET = 'UTF-8';

    /**
     * The sender address.
     *
     * @var Address|null
     */
    protected ?Address $from = null;

    /**
     * The "to" recipients.
     *
     * @var Address[]
     */
    protected array $to = [];

    /**
     * The "cc" recipients.
     *
     * @var Address[]
     */
    protected array $cc = [];

    /**
     * The "bcc" recipients.
     *
     * @var Address[]
     */
    protected array $bcc = [];

    /**
     * The "reply-to" addresses.
     *
     * @var Address[]
     */
    protected array $reply_to = [];

    /**
     * The email subject.
     *
     * @var string|null
     */
    protected ?string $subject = null;

    /**
     * The plain text body.
     *
     * @var string|null
     */
    protected ?string $text_body = null;

    /**
     * The HTML body.
     *
     * @var string|null
     */
    protected ?string $html_body = null;

    /**
     * The attachments.
     *
     * @var Attachment[]
     */
    protected array $attachments = [];

    /**
     * The custom headers.
     *
     * @var Header[]
     */
    protected array $headers = [];

    /**
     * The SES configuration set name to send under.
     *
     * @var string|null
     */
    protected ?string $configuration_set_name = null;

    /**
     * The charset used for the subject and body.
     *
     * @var string
     */
    protected string $charset = self::DEFAULT_CHARSET;

    /**
     * Sets the from address.
     *
     * @param string      $email Sender email address.
     * @param string|null $name  Optional sender display name.
     */
    public function from(string $email, ?string $name = null): self {
        $this->from = new Address($email, $name);

        return $this;
    }

    /**
     * Appends a "to" recipient. Call once per recipient.
     *
     * @param string      $email Recipient email address.
     * @param string|null $name  Optional recipient display name.
     */
    public function to(string $email, ?string $name = null): self {
        $this->to[] = new Address($email, $name);

        return $this;
    }

    /**
     * Appends a "cc" recipient. Call once per recipient.
     *
     * @param string      $email Recipient email address.
     * @param string|null $name  Optional recipient display name.
     */
    public function cc(string $email, ?string $name = null): self {
        $this->cc[] = new Address($email, $name);

        return $this;
    }

    /**
     * Appends a "bcc" recipient. Call once per recipient.
     *
     * @param string      $email Recipient email address.
     * @param string|null $name  Optional recipient display name.
     */
    public function bcc(string $email, ?string $name = null): self {
        $this->bcc[] = new Address($email, $name);

        return $this;
    }

    /**
     * Appends a "reply-to" address. Call once per address.
     *
     * @param string      $email Reply-to email address.
     * @param string|null $name  Optional display name.
     */
    public function replyTo(string $email, ?string $name = null): self {
        $this->reply_to[] = new Address($email, $name);

        return $this;
    }

    /**
     * Sets the email subject.
     *
     * @param string $subject Email subject.
     */
    public function subject(string $subject): self {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets the plain text body.
     *
     * @param string $body Plain text body.
     */
    public function text(string $body): self {
        $this->text_body = $body;

        return $this;
    }

    /**
     * Sets the HTML body.
     *
     * @param string $body HTML body.
     */
    public function html(string $body): self {
        $this->html_body = $body;

        return $this;
    }

    /**
     * Appends an attachment built from raw content.
     *
     * @param string $content      Raw binary content of the attachment.
     * @param string $filename     Filename shown to the recipient.
     * @param string $content_type MIME content type of the attachment.
     */
    public function attach(
        string $content,
        string $filename,
        string $content_type = Attachment::DEFAULT_CONTENT_TYPE
    ): self {
        $this->attachments[] = new Attachment($filename, $content, $content_type);

        return $this;
    }

    /**
     * Appends an attachment read from a file on disk.
     *
     * @param string      $path         Path to the file to attach.
     * @param string|null $filename     Filename to show the recipient.
     *                                  Defaults to the source file's
     *                                  basename.
     * @param string|null $content_type MIME content type. Detected from
     *                                  the file when not given.
     *
     * @throws Exception\AttachmentException When the file cannot be read.
     */
    public function attachFromPath(
        string $path,
        ?string $filename = null,
        ?string $content_type = null
    ): self {
        $this->attachments[] = Attachment::fromPath($path, $filename, $content_type);

        return $this;
    }

    /**
     * Adds a custom header.
     *
     * @param string $name  Header name.
     * @param string $value Header value.
     */
    public function header(string $name, string $value): self {
        $this->headers[] = new Header($name, $value);

        return $this;
    }

    /**
     * Sets the SES configuration set name to send under.
     *
     * @param string $name Configuration set name.
     */
    public function configurationSetName(string $name): self {
        $this->configuration_set_name = $name;

        return $this;
    }

    /**
     * Sets the charset used for the subject and body.
     *
     * @param string $charset Charset name, e.g. "UTF-8".
     */
    public function charset(string $charset): self {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @return bool True when this message has one or more attachments.
     */
    public function hasAttachments(): bool {
        return !empty($this->attachments);
    }

    /**
     * Validates that this message has everything required to be
     * sent: a from address, at least one recipient, a subject, and
     * at least one of a text or HTML body.
     *
     * @throws MissingRequiredFieldException When a required field is missing.
     */
    public function validate(): void {
        if ($this->from === null) {
            throw new MissingRequiredFieldException(
                'Message is missing a from address.'
            );
        }

        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            throw new MissingRequiredFieldException(
                'Message is missing at least one recipient.'
            );
        }

        if ($this->subject === null || $this->subject === '') {
            throw new MissingRequiredFieldException(
                'Message is missing a subject.'
            );
        }

        if ($this->text_body === null && $this->html_body === null) {
            throw new MissingRequiredFieldException(
                'Message is missing a text or HTML body.'
            );
        }
    }

    /**
     * @return Address|null The from address.
     */
    public function getFrom(): ?Address {
        return $this->from;
    }

    /**
     * @return Address[] The "to" recipients.
     */
    public function getTo(): array {
        return $this->to;
    }

    /**
     * @return Address[] The "cc" recipients.
     */
    public function getCc(): array {
        return $this->cc;
    }

    /**
     * @return Address[] The "bcc" recipients.
     */
    public function getBcc(): array {
        return $this->bcc;
    }

    /**
     * @return Address[] The "reply-to" addresses.
     */
    public function getReplyTo(): array {
        return $this->reply_to;
    }

    /**
     * @return string|null The email subject.
     */
    public function getSubject(): ?string {
        return $this->subject;
    }

    /**
     * @return string|null The plain text body.
     */
    public function getTextBody(): ?string {
        return $this->text_body;
    }

    /**
     * @return string|null The HTML body.
     */
    public function getHtmlBody(): ?string {
        return $this->html_body;
    }

    /**
     * @return Attachment[] The attachments.
     */
    public function getAttachments(): array {
        return $this->attachments;
    }

    /**
     * @return Header[] The custom headers.
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * @return string|null The SES configuration set name.
     */
    public function getConfigurationSetName(): ?string {
        return $this->configuration_set_name;
    }

    /**
     * @return string The charset used for the subject and body.
     */
    public function getCharset(): string {
        return $this->charset;
    }
}
