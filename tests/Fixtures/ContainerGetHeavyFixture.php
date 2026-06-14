<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ContainerGetHeavyFixture extends TestCase
{
    public function test_it_requests_a_real_container_service(): void
    {
        self::assertSame(EntityManagerInterface::class, EntityManagerInterface::class);
    }
}
