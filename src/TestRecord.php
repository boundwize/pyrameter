<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter;

final readonly class TestRecord
{
    /**
     * @param list<string> $consumedUsages Consumed class-like and function usages.
     */
    public function __construct(
        public string $testClassName,
        public string $testMethodName,
        public array $consumedUsages,
        public TestKind $kind,
    ) {
    }

    public function id(): string
    {
        return $this->testClassName . '::' . $this->testMethodName;
    }
}
