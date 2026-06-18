<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use CodeIgniter\Test\ControllerTestTrait;
use PHPUnit\Framework\TestCase;

final class CodeIgniterFunctionalFixture extends TestCase
{
    use ControllerTestTrait;

    public function testItUsesTheControllerTestRuntime(): void
    {
        $this->addToAssertionCount(1);
    }
}
