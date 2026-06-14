<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

final class SimpleUnitFixture extends TestCase
{
    public function test_it_uses_assertions(): void
    {
        $this->addToAssertionCount(1);
    }
}
