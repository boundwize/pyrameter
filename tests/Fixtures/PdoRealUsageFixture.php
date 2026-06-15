<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PDO;
use PHPUnit\Framework\TestCase;

final class PdoRealUsageFixture extends TestCase
{
    public function testItConsumesPdo(): void
    {
        new PDO('sqlite::memory:');
    }
}
