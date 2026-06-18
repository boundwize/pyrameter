<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\Rule\UsageRule;
use Boundwize\Pyrameter\Rule\UsageType;
use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\UsageClassifier;
use PHPUnit\Framework\TestCase;

final class UsageClassifierTest extends TestCase
{
    public function testNamespaceRulesMatchOnlyConfiguredPrefixCaseInsensitively(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Tests\Browser\\', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['App\Tests\Browser\CheckoutTest']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['class:App\Tests\Browser\\']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['App\Tests\Browsering\CheckoutTest']));
        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['app\Tests\Browser\CheckoutTest']));
    }

    public function testExactRulesDoNotMatchNamespacePrefixes(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Tests\Browser', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['App\Tests\Browser']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['App\Tests\Browser\CheckoutTest']));
    }

    public function testNamespaceRulesNormalizeConfiguredAndConsumedUsage(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Tests\Browser\\', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['\\APP\Tests\Browser\CheckoutTest']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['\\APP\Tests\Browsering\CheckoutTest']));
    }

    public function testNamespaceRulesRespectNamespaceSeparators(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\\', TestKind::Functional),
        ]);

        $this->assertSame(TestKind::Functional, $usageClassifier->classify(['App\Service']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['Application\Service']));
    }

    public function testExactRulesNormalizeConfiguredAndConsumedUsageButDoNotMatchPrefixes(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('file_get_contents', TestKind::Integration, UsageType::Function),
        ]);

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['function:FILE_GET_CONTENTS']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['function:FILE_GET_CONTENTS_EXTRA']));
    }

    public function testFunctionRulesDoNotMatchClassLikeUsages(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('file_get_contents', TestKind::Integration, UsageType::Function),
        ]);

        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['class:file_get_contents']));
    }

    public function testUnknownTypedConsumedUsageFallsBackToClassLikeMatching(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('custom:file_get_contents', TestKind::Integration),
        ]);

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['custom:FILE_GET_CONTENTS']));
    }

    public function testExactRulesCanShortCircuitOnE2EAfterNormalization(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('run_browser_session', TestKind::E2E, UsageType::Function),
            new UsageRule('file_get_contents', TestKind::Integration, UsageType::Function),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['function:RUN_BROWSER_SESSION']));
    }

    public function testDuplicateExactRulesUseTheHeaviestKind(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Service', TestKind::Functional),
            new UsageRule('App\Service', TestKind::Integration),
        ]);

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['App\Service']));
    }

    public function testHeaviestMatchingRuleWinsAfterRulesArePrecompiled(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\\', TestKind::Functional),
            new UsageRule('App\Tests\\', TestKind::Integration),
            new UsageRule('App\Tests\Browser\\', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['App\Tests\Browser\CheckoutTest']));
    }
}
