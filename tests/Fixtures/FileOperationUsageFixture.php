<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

use function file_get_contents;

final class FileOperationUsageFixture extends TestCase
{
    public function testItConsumesAFileOperationFunction(): void
    {
        self::assertIsString(file_get_contents(__FILE__));
    }
}
