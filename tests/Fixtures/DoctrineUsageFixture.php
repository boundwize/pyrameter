<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use stdClass;

final class DoctrineUsageFixture extends TestCase
{
    public function testItConsumesDbal(): void
    {
        $connection = DriverManager::getConnection([]);

        self::assertSame(stdClass::class, $connection::class);
    }
}
