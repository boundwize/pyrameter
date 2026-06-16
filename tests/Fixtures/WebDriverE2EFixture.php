<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

final class WebDriverE2EFixture extends TestCase
{
    public function testItUsesWebdriver(): void
    {
        $remoteWebDriver = RemoteWebDriver::create('http://localhost', []);

        $this->assertInstanceOf(RemoteWebDriver::class, $remoteWebDriver);
    }
}
