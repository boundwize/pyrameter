<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Symfony\Component\Panther\PantherTestCase;

final class PantherE2EFixture extends PantherTestCase
{
    public function test_it_uses_panther(): void
    {
        self::assertTrue(true);
    }
}
