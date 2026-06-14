<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use PDO;
use PHPUnit\Framework\TestCase;

final class MockedHeavyFixture extends TestCase
{
    public function test_it_mocks_a_heavy_dependency(): void
    {
        $this->createMock(PDO::class);
    }
}
