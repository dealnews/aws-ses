<?php

declare(strict_types=1);

namespace DealNews\AwsSes;

use Aws\ResultInterface;

/**
 * The outcome of a successful SendEmail call.
 */
class SendResult {

    /**
     * The AWS-assigned message id.
     *
     * @var string
     */
    protected string $message_id;

    /**
     * HTTP status code of the SendEmail response.
     *
     * @var int
     */
    protected int $http_status_code;

    /**
     * AWS request id, useful for support cases.
     *
     * @var string
     */
    protected string $request_id;

    /**
     * @param string $message_id       AWS-assigned message id.
     * @param int    $http_status_code HTTP status code of the SendEmail response.
     * @param string $request_id      AWS request id, useful for support cases.
     */
    public function __construct(
        string $message_id,
        int $http_status_code,
        string $request_id
    ) {
        $this->message_id       = $message_id;
        $this->http_status_code = $http_status_code;
        $this->request_id       = $request_id;
    }

    /**
     * @return string The AWS-assigned message id.
     */
    public function getMessageId(): string {
        return $this->message_id;
    }

    /**
     * @return int The HTTP status code of the SendEmail response.
     */
    public function getHttpStatusCode(): int {
        return $this->http_status_code;
    }

    /**
     * @return string The AWS request id.
     */
    public function getRequestId(): string {
        return $this->request_id;
    }

    /**
     * Builds a SendResult from the raw result returned by
     * SesV2Client::sendEmail().
     *
     * @param ResultInterface $result Raw AWS SDK result.
     */
    public static function fromAwsResult(ResultInterface $result): self {
        $metadata = $result->get('@metadata') ?? [];

        return new self(
            (string) $result->get('MessageId'),
            (int) ($metadata['statusCode'] ?? 0),
            (string) ($metadata['requestId'] ?? '')
        );
    }
}
