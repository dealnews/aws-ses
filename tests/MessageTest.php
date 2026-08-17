<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Tests;

use DealNews\AwsSes\Exception\InvalidAddressException;
use DealNews\AwsSes\Exception\MissingRequiredFieldException;
use DealNews\AwsSes\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Message::class)]
class MessageTest extends TestCase {

    protected function buildValidMessage(): Message {
        return (new Message())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Hello there');
    }

    public function testFluentBuilderAccumulatesRecipients(): void {
        $message = (new Message())
            ->to('one@example.com')
            ->to('two@example.com')
            ->cc('three@example.com')
            ->bcc('four@example.com')
            ->replyTo('five@example.com');

        $this->assertCount(2, $message->getTo());
        $this->assertCount(1, $message->getCc());
        $this->assertCount(1, $message->getBcc());
        $this->assertCount(1, $message->getReplyTo());
    }

    public function testFromWithInvalidEmailThrowsInvalidAddressException(): void {
        $this->expectException(InvalidAddressException::class);

        (new Message())->from('not-an-email');
    }

    public function testHasAttachmentsReflectsAttachmentState(): void {
        $message = new Message();

        $this->assertFalse($message->hasAttachments());

        $message->attach('data', 'file.txt');

        $this->assertTrue($message->hasAttachments());
    }

    public function testValidatePassesForCompleteMessage(): void {
        $this->expectNotToPerformAssertions();

        $this->buildValidMessage()->validate();
    }

    public function testValidateThrowsWhenFromMissing(): void {
        $message = (new Message())
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Hello there');

        $this->expectException(MissingRequiredFieldException::class);

        $message->validate();
    }

    public function testValidateThrowsWhenRecipientMissing(): void {
        $message = (new Message())
            ->from('sender@example.com')
            ->subject('Hello')
            ->text('Hello there');

        $this->expectException(MissingRequiredFieldException::class);

        $message->validate();
    }

    public function testValidateThrowsWhenSubjectMissing(): void {
        $message = (new Message())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->text('Hello there');

        $this->expectException(MissingRequiredFieldException::class);

        $message->validate();
    }

    public function testValidateThrowsWhenBodyMissing(): void {
        $message = (new Message())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello');

        $this->expectException(MissingRequiredFieldException::class);

        $message->validate();
    }
}
