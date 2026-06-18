<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Rule;

use Boundwize\Pyrameter\Rule\UsageRule;
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
        $this->assertFalse($usageRule->matches('Library\Service'));
    }

    public function testItNormalizesUsageCaseAndLeadingSlash(): void
    {
        $usageRule = new UsageRule('\file_get_contents', TestKind::Integration);

        $this->assertTrue($usageRule->matches('\FILE_GET_CONTENTS'));
    }
}
