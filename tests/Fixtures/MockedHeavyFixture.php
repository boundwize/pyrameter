<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PDO;
use PHPUnit\Framework\TestCase;

final class MockedHeavyFixture extends TestCase
{
    public function testItMocksAHeavyDependency(): void
    {
        $this->createMock(PDO::class);
    }
}
