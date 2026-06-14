<?php

declare(strict_types=1);

namespace Facebook\WebDriver\Remote;

final class RemoteWebDriver
{
    /**
     * @param array<string, mixed> $capabilities
     */
    public static function create(string $url, array $capabilities): self
    {
        return new self();
    }
}
