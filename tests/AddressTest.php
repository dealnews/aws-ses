<?php

declare(strict_types=1);

namespace DealNews\AwsSes\Tests;

use DealNews\AwsSes\Address;
use DealNews\AwsSes\Exception\InvalidAddressException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Address::class)]
class AddressTest extends TestCase {

    public function testConstructorThrowsOnInvalidEmail(): void {
        $this->expectException(InvalidAddressException::class);

        new Address('not-an-email');
    }

    public function testGettersReturnConstructorValues(): void {
        $address = new Address('customer@example.com', 'A Customer');

        $this->assertSame('customer@example.com', $address->getEmail());
        $this->assertSame('A Customer', $address->getName());
    }

    public function testToStringWithName(): void {
        $address = new Address('customer@example.com', 'A Customer');

        $this->assertSame('A Customer <customer@example.com>', $address->toString());
    }

    public function testToStringWithoutName(): void {
        $address = new Address('customer@example.com');

        $this->assertSame('customer@example.com', $address->toString());
    }
}
