<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Detection\ConsumedUsageExtractor;
use Boundwize\Pyrameter\Detection\TestUsageScanner;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\Tests\Fixtures\ContainerGetHeavyFixture;
use Boundwize\Pyrameter\Tests\Fixtures\DoctrineUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\FileOperationUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\FunctionalAndIntegrationFixture;
use Boundwize\Pyrameter\Tests\Fixtures\IntegrationAndE2EFixture;
use Boundwize\Pyrameter\Tests\Fixtures\MockedHeavyFixture;
use Boundwize\Pyrameter\Tests\Fixtures\MysqliRealUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\PantherE2EFixture;
use Boundwize\Pyrameter\Tests\Fixtures\PdoRealUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\ProductionClassOnlyFixture;
use Boundwize\Pyrameter\Tests\Fixtures\SimpleUnitFixture;
use Boundwize\Pyrameter\Tests\Fixtures\SymfonyFunctionalFixture;
use Boundwize\Pyrameter\Tests\Fixtures\WebDriverE2EFixture;
use Boundwize\Pyrameter\UsageClassifier;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_values;

final class UsageClassificationTest extends TestCase
{
    /**
     * @param class-string $fixtureClass
     */
    #[DataProvider('classificationCases')]
    public function testItClassifiesUsageFromTheTestFile(string $fixtureClass, TestKind $testKind): void
    {
        $testUsageScanner = new TestUsageScanner();
        $scanResult       = $testUsageScanner->scan($fixtureClass);
        $pyrameterConfig  = PyrameterConfig::defaults();
        $usageClassifier  = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertTrue($scanResult->inspectable, $scanResult->errorMessage ?? '');
        $this->assertSame($testKind, $usageClassifier->classify($scanResult->consumedUsages));
    }

    /**
     * @return iterable<string, array{class-string, TestKind}>
     */
    public static function classificationCases(): iterable
    {
        yield 'no heavy usage means unit' => [SimpleUnitFixture::class, TestKind::Unit];
        yield 'PDO usage means integration' => [PdoRealUsageFixture::class, TestKind::Integration];
        yield 'PDO inside production class is ignored' => [ProductionClassOnlyFixture::class, TestKind::Unit];
        yield 'mysqli usage means integration' => [MysqliRealUsageFixture::class, TestKind::Integration];
        yield 'Doctrine DBAL usage means integration' => [DoctrineUsageFixture::class, TestKind::Integration];
        yield 'file operation usage means integration' => [FileOperationUsageFixture::class, TestKind::Integration];
        yield 'Symfony WebTestCase means functional' => [SymfonyFunctionalFixture::class, TestKind::Functional];
        yield 'Panther usage means e2e' => [PantherE2EFixture::class, TestKind::E2E];
        yield 'WebDriver usage means e2e' => [WebDriverE2EFixture::class, TestKind::E2E];
        yield 'mocked heavy class stays unit' => [MockedHeavyFixture::class, TestKind::Unit];
        yield 'container class fetch is consumed' => [ContainerGetHeavyFixture::class, TestKind::Integration];
        yield 'functional plus integration chooses integration' => [
            FunctionalAndIntegrationFixture::class,
            TestKind::Integration,
        ];
        yield 'integration plus e2e chooses e2e' => [IntegrationAndE2EFixture::class, TestKind::E2E];
    }

    public function testMockTargetsAreRemovedFromConsumedClasses(): void
    {
        $scanResult = (new TestUsageScanner())->scan(MockedHeavyFixture::class);

        $this->assertTrue($scanResult->inspectable);
        $this->assertNotContains('class:PDO', $scanResult->consumedUsages);
    }

    /**
     * @param non-empty-string $className
     */
    #[DataProvider('phpRedisGlobalClassCases')]
    public function testDefaultRulesClassifyPhpRedisGlobalClassesAsIntegration(string $className): void
    {
        $pyrameterConfig = PyrameterConfig::defaults();
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::Integration, $usageClassifier->classify([$className]));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function phpRedisGlobalClassCases(): iterable
    {
        yield 'Redis client' => ['Redis'];
        yield 'Redis cluster client' => ['RedisCluster'];
        yield 'Redis sentinel client' => ['RedisSentinel'];
    }

    /**
     * @param non-empty-string $functionName
     */
    #[DataProvider('fileOperationFunctionCases')]
    public function testDefaultRulesClassifyFileOperationFunctionsAsIntegration(string $functionName): void
    {
        $pyrameterConfig = PyrameterConfig::defaults();
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['function:' . $functionName]));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function fileOperationFunctionCases(): iterable
    {
        yield 'file_get_contents' => ['file_get_contents'];
        yield 'file_put_contents' => ['file_put_contents'];
        yield 'fopen' => ['fopen'];
        yield 'fread' => ['fread'];
        yield 'fwrite' => ['fwrite'];
        yield 'fgets' => ['fgets'];
        yield 'fgetc' => ['fgetc'];
        yield 'fclose' => ['fclose'];
        yield 'feof' => ['feof'];
        yield 'rewind' => ['rewind'];
        yield 'fseek' => ['fseek'];
        yield 'ftell' => ['ftell'];
        yield 'fflush' => ['fflush'];
        yield 'ftruncate' => ['ftruncate'];
        yield 'file_exists' => ['file_exists'];
        yield 'is_file' => ['is_file'];
        yield 'is_dir' => ['is_dir'];
        yield 'is_readable' => ['is_readable'];
        yield 'is_writable' => ['is_writable'];
        yield 'is_executable' => ['is_executable'];
        yield 'filesize' => ['filesize'];
        yield 'filemtime' => ['filemtime'];
        yield 'filectime' => ['filectime'];
        yield 'fileatime' => ['fileatime'];
        yield 'fileperms' => ['fileperms'];
        yield 'fileowner' => ['fileowner'];
        yield 'filegroup' => ['filegroup'];
        yield 'filetype' => ['filetype'];
        yield 'stat' => ['stat'];
        yield 'lstat' => ['lstat'];
        yield 'touch' => ['touch'];
        yield 'copy' => ['copy'];
        yield 'rename' => ['rename'];
        yield 'unlink' => ['unlink'];
        yield 'mkdir' => ['mkdir'];
        yield 'rmdir' => ['rmdir'];
        yield 'opendir' => ['opendir'];
        yield 'readdir' => ['readdir'];
        yield 'closedir' => ['closedir'];
        yield 'rewinddir' => ['rewinddir'];
        yield 'scandir' => ['scandir'];
        yield 'glob' => ['glob'];
        yield 'chdir' => ['chdir'];
        yield 'getcwd' => ['getcwd'];
        yield 'realpath' => ['realpath'];
        yield 'chmod' => ['chmod'];
        yield 'chown' => ['chown'];
        yield 'chgrp' => ['chgrp'];
        yield 'umask' => ['umask'];
        yield 'link' => ['link'];
        yield 'symlink' => ['symlink'];
        yield 'readlink' => ['readlink'];
        yield 'is_link' => ['is_link'];
        yield 'tmpfile' => ['tmpfile'];
        yield 'tempnam' => ['tempnam'];
        yield 'flock' => ['flock'];
        yield 'is_uploaded_file' => ['is_uploaded_file'];
        yield 'move_uploaded_file' => ['move_uploaded_file'];
        yield 'fgetcsv' => ['fgetcsv'];
        yield 'fputcsv' => ['fputcsv'];
        yield 'parse_ini_file' => ['parse_ini_file'];
    }

    public function testUninspectableTestHasNoConsumedClasses(): void
    {
        $scanResult = (new TestUsageScanner())->scan('Boundwize\Pyrameter\Tests\Fixtures\MissingFixture');

        $this->assertFalse($scanResult->inspectable);
        $this->assertSame([], $scanResult->consumedUsages);
        $this->assertNotNull($scanResult->errorMessage);
    }

    public function testClassNamedLikeFileOperationFunctionStaysUnit(): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $nodes  = $parser->parse(<<<'PHP'
<?php

class file_get_contents
{
}

final class FileOperationNamedClassConsumer
{
    public function method(): void
    {
        $this->assertInstanceOf('file_get_contents', new file_get_contents());
    }
}
PHP);

        $this->assertIsArray($nodes);

        $consumedUsages  = (new ConsumedUsageExtractor())->extract(array_values($nodes));
        $pyrameterConfig = PyrameterConfig::defaults();
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame([], $consumedUsages);
        $this->assertSame(TestKind::Unit, $usageClassifier->classify($consumedUsages));
    }

    public function testClassNamedLikeFileOperationFunctionDeclaredElsewhereStaysUnit(): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $nodes  = $parser->parse(<<<'PHP'
<?php

final class FileOperationNamedClassConsumer
{
    public function method(): void
    {
        $this->assertInstanceOf('file_get_contents', new file_get_contents());
    }
}
PHP);

        $this->assertIsArray($nodes);

        $consumedUsages  = (new ConsumedUsageExtractor())->extract(array_values($nodes));
        $pyrameterConfig = PyrameterConfig::defaults();
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(['class:file_get_contents'], $consumedUsages);
        $this->assertSame(TestKind::Unit, $usageClassifier->classify($consumedUsages));
    }
}
