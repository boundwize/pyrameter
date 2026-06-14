<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FunctionalAndIntegrationFixture extends WebTestCase
{
    public function test_it_matches_two_heavy_kinds(): void
    {
        $connection = DriverManager::getConnection([]);

        self::assertSame(stdClass::class, $connection::class);
    }
}
