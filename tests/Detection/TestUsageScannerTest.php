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
    public function test_it_reports_unknown_for_internal_classes_without_source_files(): void
    {
        $result = (new TestUsageScanner())->scan(stdClass::class);

        self::assertFalse($result->inspectable);
        self::assertIsString($result->errorMessage);
        self::assertStringContainsString('could not be found', $result->errorMessage);
    }

    public function test_it_reports_unknown_when_the_source_file_cannot_be_read(): void
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

    public function test_it_reports_unknown_when_the_source_file_cannot_be_parsed(): void
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
