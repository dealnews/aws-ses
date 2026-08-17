<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Tests;

use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\SesV2\Exception\SesV2Exception;
use Aws\SesV2\SesV2Client;
use DealNews\AwsSes\Exception\SendException;
use DealNews\AwsSes\Mailer;
use DealNews\AwsSes\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mailer::class)]
class MailerTest extends TestCase {

    protected function buildClient(MockHandler $mock_handler): SesV2Client {
        return new SesV2Client([
            'region'      => 'us-east-1',
            'version'     => '2019-09-27',
            'handler'     => $mock_handler,
            'credentials' => ['key' => 'test', 'secret' => 'test'],
        ]);
    }

    protected function buildMessage(): Message {
        return (new Message())
            ->from('sender@example.com', 'Sender')
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Hello there');
    }

    public function testSendReturnsSendResultFromAwsResponse(): void {
        $mock_handler = new MockHandler();
        $mock_handler->append(new Result([
            'MessageId' => 'test-message-id',
            '@metadata' => [
                'statusCode' => 200,
                'requestId'  => 'test-request-id',
            ],
        ]));

        $mailer = new Mailer($this->buildClient($mock_handler));
        $result = $mailer->send($this->buildMessage());

        $this->assertSame('test-message-id', $result->getMessageId());
        $this->assertSame(200, $result->getHttpStatusCode());
        $this->assertSame('test-request-id', $result->getRequestId());
    }

    public function testSendBuildsExpectedRequestParams(): void {
        $mock_handler = new MockHandler();
        $mock_handler->append(function (CommandInterface $command): Result {
            $this->assertSame('Sender <sender@example.com>', $command['FromEmailAddress'] ?? null);
            $this->assertSame(['recipient@example.com'], $command['Destination']['ToAddresses'] ?? null);
            $this->assertSame('Hello', $command['Content']['Simple']['Subject']['Data'] ?? null);

            return new Result(['MessageId' => 'test-message-id']);
        });

        $mailer = new Mailer($this->buildClient($mock_handler));
        $mailer->send($this->buildMessage());
    }

    public function testSendIncludesAttachmentsInRequestContent(): void {
        $mock_handler = new MockHandler();
        $mock_handler->append(function (CommandInterface $command): Result {
            $attachments = $command['Content']['Simple']['Attachments'] ?? [];

            $this->assertCount(1, $attachments);
            $this->assertSame('invoice.pdf', $attachments[0]['FileName']);
            $this->assertSame('pdf bytes', $attachments[0]['RawContent']);
            $this->assertSame('BASE64', $attachments[0]['ContentTransferEncoding']);

            return new Result(['MessageId' => 'test-message-id']);
        });

        $mailer  = new Mailer($this->buildClient($mock_handler));
        $message = $this->buildMessage()->attach('pdf bytes', 'invoice.pdf', 'application/pdf');

        $mailer->send($message);
    }

    public function testSendWrapsAwsExceptionInSendException(): void {
        $mock_handler = new MockHandler();
        $mock_handler->append(new SesV2Exception('Throttled', $this->createMock(CommandInterface::class)));

        $mailer = new Mailer($this->buildClient($mock_handler));

        try {
            $mailer->send($this->buildMessage());
            $this->fail('Expected SendException to be thrown.');
        } catch (SendException $e) {
            $this->assertInstanceOf(SesV2Exception::class, $e->getPrevious());
        }
    }
}
