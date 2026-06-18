<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests;

use Boundwize\Pyrameter\Rule\UsageRule;
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

    public function testExactRulesNormalizeConfiguredAndConsumedUsageButDoNotMatchPrefixes(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('file_get_contents', TestKind::Integration),
        ]);

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['FILE_GET_CONTENTS']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['FILE_GET_CONTENTS_EXTRA']));
    }

    public function testExactRulesCanShortCircuitOnE2EAfterNormalization(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('run_browser_session', TestKind::E2E),
            new UsageRule('file_get_contents', TestKind::Integration),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['RUN_BROWSER_SESSION']));
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
