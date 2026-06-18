<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Config;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\UsageClassifier;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

use function strtolower;

final class PyrameterConfigTest extends TestCase
{
    public function testTargetShapePercentagesMustNotBeNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be zero or greater');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => -1],
        );
    }

    public function testTargetShapePercentagesMustNotExceedOneHundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be 100 or less');

        PyrameterConfig::create()->targetShape(
            unit: ['max' => 101],
        );
    }

    public function testTargetShapeMinimumTotalCannotExceedOneHundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('minimum percentages cannot exceed 100.0');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => 80],
            functional: ['min' => 30],
        );
    }

    public function testUsesNamespaceNormalizesTrailingBackslash(): void
    {
        $pyrameterConfig = PyrameterConfig::create()->usesNamespace('App\Tests\Browser', TestKind::E2E);
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['App\Tests\Browser\Checkout']));
    }

    public function testUsesClassMatchesClassUsageCaseInsensitively(): void
    {
        $pyrameterConfig = PyrameterConfig::create()->usesClass(PDO::class, TestKind::Integration);
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['\\' . strtolower(PDO::class)]));
    }

    public function testUsesNamespaceMatchesNamespaceUsageCaseInsensitively(): void
    {
        $pyrameterConfig = PyrameterConfig::create()->usesNamespace('App\Tests\Browser', TestKind::E2E);
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['\aPp\tEsTs\bRoWsEr\Checkout']));
    }

    public function testUsesFunctionMatchesFunctionUsageCaseInsensitively(): void
    {
        $pyrameterConfig = PyrameterConfig::create()->usesFunction('file_get_contents', TestKind::Integration);
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['\FILE_GET_CONTENTS']));
    }
}
