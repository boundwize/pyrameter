<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use stdClass;
use Symfony\Component\Panther\PantherTestCase;

final class IntegrationAndE2EFixture extends PantherTestCase
{
    public function testItMatchesTheHeaviestKind(): void
    {
        $connection = DriverManager::getConnection([]);

        self::assertSame(stdClass::class, $connection::class);
    }
}
