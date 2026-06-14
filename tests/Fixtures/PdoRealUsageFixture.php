<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use PDO;
use PHPUnit\Framework\TestCase;

final class PdoRealUsageFixture extends TestCase
{
    public function test_it_consumes_pdo(): void
    {
        new PDO('sqlite::memory:');
    }
}
