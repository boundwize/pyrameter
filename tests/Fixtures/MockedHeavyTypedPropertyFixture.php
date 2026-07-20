<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MockedHeavyTypedPropertyFixture extends TestCase
{
    private PDO&MockObject $pdo;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
    }

    public function testItMocksAHeavyDependency(): void
    {
        $this->pdo->expects($this->never())->method('query');
    }
}
