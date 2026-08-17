<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Tests;

use DealNews\AwsSes\Attachment;
use DealNews\AwsSes\Exception\AttachmentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Attachment::class)]
class AttachmentTest extends TestCase {

    protected string $fixture_path;

    protected function setUp(): void {
        $this->fixture_path = __DIR__ . '/fixtures/sample-attachment.txt';
    }

    public function testConstructorSetsFields(): void {
        $attachment = new Attachment('report.csv', 'a,b,c', 'text/csv');

        $this->assertSame('report.csv', $attachment->getFilename());
        $this->assertSame('a,b,c', $attachment->getContent());
        $this->assertSame('text/csv', $attachment->getContentType());
    }

    public function testConstructorDefaultsContentType(): void {
        $attachment = new Attachment('file.bin', 'data');

        $this->assertSame(Attachment::DEFAULT_CONTENT_TYPE, $attachment->getContentType());
    }

    public function testFromPathReadsFileContentAndDefaultsFilename(): void {
        $attachment = Attachment::fromPath($this->fixture_path);

        $this->assertSame('sample-attachment.txt', $attachment->getFilename());
        $this->assertSame(file_get_contents($this->fixture_path), $attachment->getContent());
    }

    public function testFromPathUsesGivenFilenameAndContentType(): void {
        $attachment = Attachment::fromPath($this->fixture_path, 'custom.txt', 'text/plain');

        $this->assertSame('custom.txt', $attachment->getFilename());
        $this->assertSame('text/plain', $attachment->getContentType());
    }

    public function testFromPathThrowsWhenFileMissing(): void {
        $this->expectException(AttachmentException::class);

        Attachment::fromPath('/no/such/file.txt');
    }
}
