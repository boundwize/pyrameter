<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class DoctrineUsageFixture extends TestCase
{
    public function test_it_consumes_dbal(): void
    {
        $connection = DriverManager::getConnection([]);

        self::assertSame(\stdClass::class, $connection::class);
    }
}
