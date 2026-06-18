<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use CodeIgniter\Test\DatabaseTestTrait;
use PHPUnit\Framework\TestCase;

final class CodeIgniterIntegrationFixture extends TestCase
{
    use DatabaseTestTrait;

    public function testItUsesTheDatabaseTestRuntime(): void
    {
        $this->addToAssertionCount(1);
    }
}
