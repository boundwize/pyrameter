<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\Panther\PantherTestCase;

final class IntegrationAndE2EFixture extends PantherTestCase
{
    public function test_it_matches_the_heaviest_kind(): void
    {
        DriverManager::getConnection([]);
    }
}
