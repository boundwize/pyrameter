<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

use function tmpfile;

final class FileOperationUsageFixture extends TestCase
{
    public function testItConsumesAFileOperationFunction(): void
    {
        self::assertIsResource(tmpfile());
    }
}
