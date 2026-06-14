<?php

declare(strict_types=1);

namespace Pyrameter\Tests;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pyrameter\Config\PyrameterConfig;
use Pyrameter\Detection\TestUsageScanner;
use Pyrameter\TestKind;
use Pyrameter\Tests\Fixtures\ContainerGetHeavyFixture;
use Pyrameter\Tests\Fixtures\DoctrineUsageFixture;
use Pyrameter\Tests\Fixtures\FunctionalAndIntegrationFixture;
use Pyrameter\Tests\Fixtures\IntegrationAndE2EFixture;
use Pyrameter\Tests\Fixtures\MockedHeavyFixture;
use Pyrameter\Tests\Fixtures\MysqliRealUsageFixture;
use Pyrameter\Tests\Fixtures\PantherE2EFixture;
use Pyrameter\Tests\Fixtures\PdoRealUsageFixture;
use Pyrameter\Tests\Fixtures\ProductionClassOnlyFixture;
use Pyrameter\Tests\Fixtures\SimpleUnitFixture;
use Pyrameter\Tests\Fixtures\SymfonyFunctionalFixture;
use Pyrameter\Tests\Fixtures\WebDriverE2EFixture;
use Pyrameter\UsageClassifier;

final class UsageClassificationTest extends TestCase
{
    /**
     * @param class-string $fixtureClass
     */
    #[DataProvider('classificationCases')]
    public function test_it_classifies_usage_from_the_test_file(string $fixtureClass, TestKind $expectedKind): void
    {
        $scanner = new TestUsageScanner();
        $scanResult = $scanner->scan($fixtureClass);
        $config = PyrameterConfig::defaults();
        $classifier = new UsageClassifier($config->usageRules());

        self::assertTrue($scanResult->inspectable, $scanResult->errorMessage ?? '');
        self::assertSame($expectedKind, $classifier->classify($scanResult->consumedClasses));
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
        yield 'Symfony WebTestCase means functional' => [SymfonyFunctionalFixture::class, TestKind::Functional];
        yield 'Panther usage means e2e' => [PantherE2EFixture::class, TestKind::E2E];
        yield 'WebDriver usage means e2e' => [WebDriverE2EFixture::class, TestKind::E2E];
        yield 'mocked heavy class stays unit' => [MockedHeavyFixture::class, TestKind::Unit];
        yield 'container class fetch is consumed' => [ContainerGetHeavyFixture::class, TestKind::Integration];
        yield 'functional plus integration chooses integration' => [FunctionalAndIntegrationFixture::class, TestKind::Integration];
        yield 'integration plus e2e chooses e2e' => [IntegrationAndE2EFixture::class, TestKind::E2E];
    }

    public function test_mock_targets_are_removed_from_consumed_classes(): void
    {
        $scanResult = (new TestUsageScanner())->scan(MockedHeavyFixture::class);

        self::assertTrue($scanResult->inspectable);
        self::assertNotContains(PDO::class, $scanResult->consumedClasses);
    }

    public function test_uninspectable_test_means_unknown(): void
    {
        $scanResult = (new TestUsageScanner())->scan('Pyrameter\Tests\Fixtures\MissingFixture');

        self::assertFalse($scanResult->inspectable);
        self::assertSame([], $scanResult->consumedClasses);
        self::assertNotNull($scanResult->errorMessage);
    }
}
