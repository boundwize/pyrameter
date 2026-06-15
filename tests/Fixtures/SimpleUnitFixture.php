<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

final class SimpleUnitFixture extends TestCase
{
    public function testItUsesAssertions(): void
    {
        $this->addToAssertionCount(1);
    }
}
