<?php

declare(strict_types=1);

namespace DealNews\AwsSes;

/**
 * Builds the "Content" parameter of an SES v2 SendEmail request
 * from a Message. Always builds Simple content, since SES v2's
 * Simple content natively supports both custom headers and file
 * attachments.
 */
class EmailContentBuilder {

    /**
     * @param Message $message Message to build SES Content for.
     *
     * @return array<string, mixed> The "Content" parameter for SendEmail.
     */
    public function build(Message $message): array {
        $email = [
            'Subject' => [
                'Charset' => $message->getCharset(),
                'Data'    => (string) $message->getSubject(),
            ],
            'Body' => $this->buildBody($message),
        ];

        if (!empty($message->getHeaders())) {
            $email['Headers'] = $this->buildHeaders($message);
        }

        if (!empty($message->getAttachments())) {
            $email['Attachments'] = $this->buildAttachments($message);
        }

        return [
            'Simple' => $email,
        ];
    }

    /**
     * @param Message $message Message to build a Body for.
     *
     * @return array<string, mixed>
     */
    protected function buildBody(Message $message): array {
        $body = [];

        if ($message->getTextBody() !== null) {
            $body['Text'] = [
                'Charset' => $message->getCharset(),
                'Data'    => $message->getTextBody(),
            ];
        }

        if ($message->getHtmlBody() !== null) {
            $body['Html'] = [
                'Charset' => $message->getCharset(),
                'Data'    => $message->getHtmlBody(),
            ];
        }

        return $body;
    }

    /**
     * @param Message $message Message to build a Headers list for.
     *
     * @return array<int, array<string, string>>
     */
    protected function buildHeaders(Message $message): array {
        $headers = [];

        foreach ($message->getHeaders() as $header) {
            $headers[] = [
                'Name'  => $header->getName(),
                'Value' => $header->getValue(),
            ];
        }

        return $headers;
    }

    /**
     * @param Message $message Message to build an Attachments list for.
     *
     * @return array<int, array<string, string>>
     */
    protected function buildAttachments(Message $message): array {
        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            $attachments[] = [
                'RawContent'              => $attachment->getContent(),
                'FileName'                => $attachment->getFilename(),
                'ContentType'             => $attachment->getContentType(),
                'ContentTransferEncoding' => 'BASE64',
            ];
        }

        return $attachments;
    }
}
