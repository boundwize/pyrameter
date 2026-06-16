<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter;

use function array_values;

final class TestCollector
{
    /** @var array<string, TestRecord> */
    private array $records = [];

    public function add(TestRecord $testRecord): void
    {
        $this->records[$testRecord->id()] = $testRecord;
    }

    /**
     * @return list<TestRecord>
     */
    public function all(): array
    {
        return array_values($this->records);
    }
}
