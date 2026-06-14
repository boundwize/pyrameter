<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SymfonyFunctionalFixture extends WebTestCase
{
    public function test_it_uses_the_framework_runtime(): void
    {
        $this->addToAssertionCount(1);
    }
}
