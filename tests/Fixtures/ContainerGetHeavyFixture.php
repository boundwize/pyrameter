<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ContainerGetHeavyFixture extends TestCase
{
    public function testItRequestsARealContainerService(): void
    {
        $this->assertSame(EntityManagerInterface::class, EntityManagerInterface::class);
    }
}
