<?php

declare(strict_types=1);

namespace Pyrameter;

final readonly class TestRecord
{
    /**
     * @param list<class-string> $consumedClasses
     */
    public function __construct(
        public string $testClassName,
        public string $testMethodName,
        public array $consumedClasses,
        public TestKind $kind,
    ) {
    }

    public function id(): string
    {
        return $this->testClassName . '::' . $this->testMethodName;
    }
}
