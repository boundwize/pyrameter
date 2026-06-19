<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Analysis;

use Boundwize\Pyrameter\TestKind;
use Boundwize\Pyrameter\ValueObject\PyramidSummary;
use Boundwize\Pyrameter\ValueObject\TestRecord;

use function explode;

final class TestCollector
{
    /** @var array<string, TestKind> */
    private array $kindByTestId = [];

    /** @var array<string, int> */
    private array $counts = [
        'unit'        => 0,
        'functional'  => 0,
        'integration' => 0,
        'e2e'         => 0,
    ];

    public function add(TestRecord $testRecord): void
    {
        $testId       = $testRecord->id();
        $existingKind = $this->kindByTestId[$testId] ?? null;

        if ($existingKind === $testRecord->kind) {
            return;
        }

        if ($existingKind instanceof TestKind) {
            --$this->counts[$existingKind->value];
        }

        $this->kindByTestId[$testId] = $testRecord->kind;
        ++$this->counts[$testRecord->kind->value];
    }

    public function summary(): PyramidSummary
    {
        return PyramidSummary::fromCounts($this->counts);
    }

    /**
     * @return list<TestRecord>
     */
    public function all(): array
    {
        $records = [];

        foreach ($this->kindByTestId as $testId => $testKind) {
            [$testClassName, $testMethodName] = explode('::', $testId, 2);
            $records[]                        = new TestRecord($testClassName, $testMethodName, [], $testKind);
        }

        return $records;
    }
}
