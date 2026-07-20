<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Detection;

use Boundwize\Pyrameter\Analysis\UsageClassifier;
use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Detection\ConsumedUsageExtractor;
use Boundwize\Pyrameter\Detection\TestUsageScanner;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\Tests\Fixtures\CodeIgniterFunctionalFixture;
use Boundwize\Pyrameter\Tests\Fixtures\CodeIgniterIntegrationFixture;
use Boundwize\Pyrameter\Tests\Fixtures\ContainerGetHeavyFixture;
use Boundwize\Pyrameter\Tests\Fixtures\DoctrineUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\FileOperationUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\FunctionalAndIntegrationFixture;
use Boundwize\Pyrameter\Tests\Fixtures\IntegrationAndE2EFixture;
use Boundwize\Pyrameter\Tests\Fixtures\MockedHeavyFixture;
use Boundwize\Pyrameter\Tests\Fixtures\MockedHeavyTypedPropertyFixture;
use Boundwize\Pyrameter\Tests\Fixtures\MysqliRealUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\PantherE2EFixture;
use Boundwize\Pyrameter\Tests\Fixtures\PdoRealUsageFixture;
use Boundwize\Pyrameter\Tests\Fixtures\ProductionClassOnlyFixture;
use Boundwize\Pyrameter\Tests\Fixtures\SimpleUnitFixture;
use Boundwize\Pyrameter\Tests\Fixtures\SymfonyFunctionalFixture;
use Boundwize\Pyrameter\Tests\Fixtures\WebDriverE2EFixture;
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
        yield 'CodeIgniter controller and database traits mean functional' => [
            CodeIgniterFunctionalFixture::class,
            TestKind::Functional,
        ];
        yield 'CodeIgniter DatabaseTestTrait means integration' => [
            CodeIgniterIntegrationFixture::class,
            TestKind::Integration,
        ];
        yield 'Panther usage means e2e' => [PantherE2EFixture::class, TestKind::E2E];
        yield 'WebDriver usage means e2e' => [WebDriverE2EFixture::class, TestKind::E2E];
        yield 'mocked heavy class stays unit' => [MockedHeavyFixture::class, TestKind::Unit];
        yield 'mocked heavy class in typed property stays unit' => [
            MockedHeavyTypedPropertyFixture::class,
            TestKind::Unit,
        ];
        yield 'container class fetch is consumed' => [ContainerGetHeavyFixture::class, TestKind::Integration];
        yield 'functional plus integration chooses integration' => [
            FunctionalAndIntegrationFixture::class,
            TestKind::Integration,
        ];
        yield 'integration plus e2e chooses e2e' => [IntegrationAndE2EFixture::class, TestKind::E2E];
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

        $this->assertSame(['class:file_get_contents'], $consumedUsages);
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
