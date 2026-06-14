<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use mysqli;
use PHPUnit\Framework\TestCase;

final class MysqliRealUsageFixture extends TestCase
{
    public function testItConsumesMysqli(): void
    {
        new mysqli('localhost', 'user', 'password');
    }
}
