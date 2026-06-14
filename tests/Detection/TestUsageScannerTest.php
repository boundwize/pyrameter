<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Detection;

use PHPUnit\Framework\TestCase;
use Pyrameter\Detection\TestUsageScanner;
use ReflectionClass;
use stdClass;

use function chmod;
use function class_exists;
use function file_put_contents;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class TestUsageScannerTest extends TestCase
{
    public function testItReportsUnknownForInternalClassesWithoutSourceFiles(): void
    {
        $result = (new TestUsageScanner())->scan(stdClass::class);

        self::assertFalse($result->inspectable);
        self::assertIsString($result->errorMessage);
        self::assertStringContainsString('could not be found', $result->errorMessage);
    }

    public function testItReportsUnknownWhenTheSourceFileCannotBeRead(): void
    {
        $className = $this->createTemporaryClass();
        $fileName  = (new ReflectionClass($className))->getFileName();

        self::assertIsString($fileName);

        chmod($fileName, 0000);
        set_error_handler(static fn (): bool => true);

        try {
            $result = (new TestUsageScanner())->scan($className);
        } finally {
            restore_error_handler();
            chmod($fileName, 0644);
            unlink($fileName);
        }

        self::assertFalse($result->inspectable);
        self::assertIsString($result->errorMessage);
        self::assertStringContainsString('could not be read', $result->errorMessage);
    }

    public function testItReportsUnknownWhenTheSourceFileCannotBeParsed(): void
    {
        $className = $this->createTemporaryClass();
        $fileName  = (new ReflectionClass($className))->getFileName();

        self::assertIsString($fileName);

        file_put_contents($fileName, "<?php\nfinal class Broken {");

        try {
            $result = (new TestUsageScanner())->scan($className);
        } finally {
            unlink($fileName);
        }

        self::assertFalse($result->inspectable);
        self::assertIsString($result->errorMessage);
        self::assertStringContainsString('could not be parsed:', $result->errorMessage);
    }

    /**
     * @return class-string
     */
    private function createTemporaryClass(): string
    {
        $className = 'PyrameterScannerFixture' . str_replace('.', '', uniqid('', true));
        $fileName  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $className . '.php';

        file_put_contents($fileName, sprintf("<?php\nfinal class %s {}\n", $className));
        require $fileName;

        if (! class_exists($className)) {
            self::fail(sprintf('Temporary class "%s" was not loaded.', $className));
        }

        return $className;
    }
}
