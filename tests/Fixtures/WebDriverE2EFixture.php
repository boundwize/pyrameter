<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

final class WebDriverE2EFixture extends TestCase
{
    public function test_it_uses_webdriver(): void
    {
        RemoteWebDriver::create('http://localhost', []);
    }
}
