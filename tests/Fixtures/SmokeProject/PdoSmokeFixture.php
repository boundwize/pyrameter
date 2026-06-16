<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures\SmokeProject;

use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSmokeFixture extends TestCase
{
    public function testOne(): void
    {
        self::assertSame(PDO::class, PDO::class);
    }

    public function testTwo(): void
    {
        $this->addToAssertionCount(1);
    }
}
