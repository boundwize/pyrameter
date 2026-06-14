<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FunctionalAndIntegrationFixture extends WebTestCase
{
    public function testItMatchesTwoHeavyKinds(): void
    {
        $connection = DriverManager::getConnection([]);

        self::assertSame(stdClass::class, $connection::class);
    }
}
