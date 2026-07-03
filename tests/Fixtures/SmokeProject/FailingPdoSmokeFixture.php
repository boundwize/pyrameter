<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures\SmokeProject;

use PDO;
use PHPUnit\Framework\TestCase;

final class FailingPdoSmokeFixture extends TestCase
{
    public function testFails(): void
    {
        self::assertSame('expected', PDO::class);
    }
}
