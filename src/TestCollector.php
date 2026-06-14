<?php

declare(strict_types=1);

namespace Pyrameter;

final class TestCollector
{
    /**
     * @var array<string, TestRecord>
     */
    private array $records = [];

    public function add(TestRecord $record): void
    {
        $this->records[$record->id()] = $record;
    }

    /**
     * @return list<TestRecord>
     */
    public function all(): array
    {
        return array_values($this->records);
    }
}
