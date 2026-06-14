<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures\SmokeProject;

use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSmokeFixture extends TestCase
{
    public function test_one(): void
    {
        $this->addToAssertionCount(1);
    }

    public function test_two(): void
    {
        $this->addToAssertionCount(1);
    }
}
