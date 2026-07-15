<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Rule;

use Boundwize\Pyrameter\Rule\UsageRule;
use Boundwize\Pyrameter\Rule\UsageType;
use Boundwize\Pyrameter\TestKind;
use PHPUnit\Framework\TestCase;

final class UsageRuleTest extends TestCase
{
    public function testItMatchesExactUsage(): void
    {
        $usageRule = new UsageRule('App\Service', TestKind::Integration);

        $this->assertTrue($usageRule->matches('App\Service'));
    }

    public function testExactUsageDoesNotMatchPrefixes(): void
    {
        $usageRule = new UsageRule('App\Service', TestKind::Integration);

        $this->assertFalse($usageRule->matches('App\Service\Child'));
    }

    public function testItMatchesNamespacePrefixes(): void
    {
        $usageRule = new UsageRule('App\\', TestKind::Functional);

        $this->assertTrue($usageRule->matches('App\Service'));
        $this->assertFalse($usageRule->matches('App\\'));
        $this->assertFalse($usageRule->matches('Application\Service'));
        $this->assertFalse($usageRule->matches('Library\Service'));
    }

    public function testItNormalizesUsageCaseAndLeadingSlash(): void
    {
        $usageRule = new UsageRule('\file_get_contents', TestKind::Integration, UsageType::Function);

        $this->assertTrue($usageRule->matches('function:\FILE_GET_CONTENTS'));
    }

    public function testItExposesNormalizedUsage(): void
    {
        $usageRule = new UsageRule('\APP\Service', TestKind::Integration);

        $this->assertSame('app\service', $usageRule->normalizedUsage());
    }

    public function testItDoesNotMatchDifferentUsageTypes(): void
    {
        $usageRule = new UsageRule('file_get_contents', TestKind::Integration, UsageType::Function);

        $this->assertFalse($usageRule->matches('class:file_get_contents'));
    }

    public function testItTreatsUnknownTypedConsumedUsageAsRawUsage(): void
    {
        $usageRule = new UsageRule('custom:file_get_contents', TestKind::Integration);

        $this->assertTrue($usageRule->matches('custom:FILE_GET_CONTENTS'));
    }

    public function testUsageTypesExposeStablePrefixes(): void
    {
        $this->assertSame('class', UsageType::ClassLike->value);
        $this->assertSame('function', UsageType::Function->value);
    }

    public function testItNormalizesUnlessUsages(): void
    {
        $usageRule = new UsageRule(
            'Framework\DatabaseTrait',
            TestKind::Integration,
            unless: ['\FRAMEWORK\ControllerTrait'],
        );

        $this->assertSame(['class:framework\controllertrait'], $usageRule->normalizedUnlessKeys());
    }

    public function testItNormalizesFunctionRuleUnlessUsagesAsClassLike(): void
    {
        $usageRule = new UsageRule(
            'file_put_contents',
            TestKind::Integration,
            UsageType::Function,
            unless: ['\APP\Tests\UsesVirtualFilesystem'],
        );

        $this->assertSame(['class:app\tests\usesvirtualfilesystem'], $usageRule->normalizedUnlessKeys());
    }
}
