<?php

declare(strict_types=1);

namespace DealNews\AwsSes;

/**
 * A single custom email header name/value pair.
 */
class Header {

    /**
     * The header name, e.g. "X-DealNews-Category".
     *
     * @var string
     */
    protected string $name;

    /**
     * The header value.
     *
     * @var string
     */
    protected string $value;

    /**
     * @param string $name  Header name, e.g. "X-DealNews-Category".
     * @param string $value Header value.
     */
    public function __construct(string $name, string $value) {
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * @return string The header name.
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string The header value.
     */
    public function getValue(): string {
        return $this->value;
    }
}
