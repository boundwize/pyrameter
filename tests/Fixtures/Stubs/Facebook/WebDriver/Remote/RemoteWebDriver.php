<?php

declare(strict_types=1);

namespace Facebook\WebDriver\Remote;

final class RemoteWebDriver
{
    public static function create(): self
    {
        return new self();
    }
}
