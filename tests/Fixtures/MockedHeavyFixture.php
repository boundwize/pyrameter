<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use PDO;
use PHPUnit\Framework\TestCase;

final class MockedHeavyFixture extends TestCase
{
    public function testItMocksAHeavyDependency(): void
    {
        $this->createMock(PDO::class);
    }
}
