<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Symfony\Component\Panther\PantherTestCase;

final class PantherE2EFixture extends PantherTestCase
{
    public function testItUsesPanther(): void
    {
        $this->addToAssertionCount(1);
    }
}
