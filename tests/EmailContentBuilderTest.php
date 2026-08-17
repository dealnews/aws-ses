<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Tests;

use DealNews\AwsSes\EmailContentBuilder;
use DealNews\AwsSes\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailContentBuilder::class)]
class EmailContentBuilderTest extends TestCase {

    protected EmailContentBuilder $builder;

    protected function setUp(): void {
        $this->builder = new EmailContentBuilder();
    }

    public function testBuildOmitsHeadersAndAttachmentsWhenNotSet(): void {
        $message = (new Message())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Hello there')
            ->html('<p>Hello there</p>');

        $content = $this->builder->build($message);

        $this->assertSame('Hello', $content['Simple']['Subject']['Data']);
        $this->assertSame('Hello there', $content['Simple']['Body']['Text']['Data']);
        $this->assertSame('<p>Hello there</p>', $content['Simple']['Body']['Html']['Data']);
        $this->assertArrayNotHasKey('Headers', $content['Simple']);
        $this->assertArrayNotHasKey('Attachments', $content['Simple']);
    }

    public function testBuildIncludesHeadersWhenPresent(): void {
        $message = (new Message())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Hello there')
            ->header('X-DealNews-Category', 'transactional');

        $content = $this->builder->build($message);

        $this->assertSame(
            [['Name' => 'X-DealNews-Category', 'Value' => 'transactional']],
            $content['Simple']['Headers']
        );
    }

    public function testBuildIncludesAttachmentsWhenPresent(): void {
        $message = (new Message())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Hello')
            ->text('Hello there')
            ->attach('file bytes', 'notes.txt', 'text/plain');

        $content = $this->builder->build($message);

        $this->assertSame(
            [[
                'RawContent'              => 'file bytes',
                'FileName'                => 'notes.txt',
                'ContentType'             => 'text/plain',
                'ContentTransferEncoding' => 'BASE64',
            ]],
            $content['Simple']['Attachments']
        );
    }
}
