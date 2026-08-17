<?php

declare(strict_types=1);

namespace DealNews\AwsSes;

use Aws\SesV2\SesV2Client;
use DealNews\AwsSes\Exception\SendException;

/**
 * Sends Message objects through AWS SES v2.
 */
class Mailer {

    /**
     * Configured SES v2 client used to send messages.
     *
     * @var SesV2Client
     */
    protected SesV2Client $ses_client;

    /**
     * Builder used to construct the SendEmail "Content" parameter.
     *
     * @var EmailContentBuilder
     */
    protected EmailContentBuilder $content_builder;

    /**
     * @param SesV2Client              $ses_client      Configured SES v2 client.
     * @param EmailContentBuilder|null $content_builder Builder used to
     *                                                   construct the
     *                                                   SendEmail "Content"
     *                                                   parameter.
     */
    public function __construct(
        SesV2Client $ses_client,
        ?EmailContentBuilder $content_builder = null
    ) {
        $this->ses_client      = $ses_client;
        $this->content_builder = $content_builder ?? new EmailContentBuilder();
    }

    /**
     * Validates and sends a message through AWS SES.
     *
     * @param Message $message Message to send.
     *
     * @throws Exception\MissingRequiredFieldException When the message is
     *                                                  missing a required
     *                                                  field.
     * @throws SendException When AWS SES fails to process the request.
     */
    public function send(Message $message): SendResult {
        $message->validate();

        try {
            $request  = $this->buildRequestParams($message);
            $response = $this->ses_client->sendEmail($request);
        } catch (\Throwable $e) {
            throw new SendException(
                sprintf('Failed to send email via AWS SES: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e
            );
        }

        return SendResult::fromAwsResult($response);
    }

    /**
     * @param Message $message Message to build a SendEmail request for.
     *
     * @return array<string, mixed>
     */
    protected function buildRequestParams(Message $message): array {
        $params = [
            'Content'     => $this->content_builder->build($message),
            'Destination' => $this->buildDestination($message),
        ];

        $from = $message->getFrom();

        if ($from !== null) {
            $params['FromEmailAddress'] = $from->toString();
        }

        if (!empty($message->getReplyTo())) {
            $params['ReplyToAddresses'] = array_map(
                static fn (Address $address): string => $address->toString(),
                $message->getReplyTo()
            );
        }

        if ($message->getConfigurationSetName() !== null) {
            $params['ConfigurationSetName'] = $message->getConfigurationSetName();
        }

        return $params;
    }

    /**
     * @param Message $message Message to build a Destination for.
     *
     * @return array<string, string[]>
     */
    protected function buildDestination(Message $message): array {
        $destination = [];

        if (!empty($message->getTo())) {
            $destination['ToAddresses'] = array_map(
                static fn (Address $address): string => $address->toString(),
                $message->getTo()
            );
        }

        if (!empty($message->getCc())) {
            $destination['CcAddresses'] = array_map(
                static fn (Address $address): string => $address->toString(),
                $message->getCc()
            );
        }

        if (!empty($message->getBcc())) {
            $destination['BccAddresses'] = array_map(
                static fn (Address $address): string => $address->toString(),
                $message->getBcc()
            );
        }

        return $destination;
    }
}
