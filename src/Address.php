<?php

declare(strict_types=1);

namespace DealNews\AwsSes;

use DealNews\AwsSes\Exception\InvalidAddressException;

/**
 * A validated email address with an optional display name.
 */
class Address {

    /**
     * The validated email address.
     *
     * @var string
     */
    protected string $email;

    /**
     * Optional display name shown alongside the email address.
     *
     * @var string|null
     */
    protected ?string $name;

    /**
     * @param string      $email Email address to validate and store.
     * @param string|null $name  Optional display name.
     *
     * @throws InvalidAddressException When $email is not a valid email address.
     */
    public function __construct(string $email, ?string $name = null) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidAddressException(
                sprintf('"%s" is not a valid email address.', $email)
            );
        }

        $this->email = $email;
        $this->name  = $name;
    }

    /**
     * @return string The validated email address.
     */
    public function getEmail(): string {
        return $this->email;
    }

    /**
     * @return string|null The display name, if one was given.
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * Renders the address as an RFC 5322 mailbox string, e.g.
     * "Display Name <email@example.com>" or, with no name, just
     * "email@example.com".
     *
     * @return string
     */
    public function toString(): string {
        $return = $this->email;

        if ($this->name !== null && $this->name !== '') {
            $return = sprintf('%s <%s>', $this->name, $this->email);
        }

        return $return;
    }
}
