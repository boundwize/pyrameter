<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SymfonyFunctionalFixture extends WebTestCase
{
    public function testItUsesTheFrameworkRuntime(): void
    {
        $this->addToAssertionCount(1);
    }
}
