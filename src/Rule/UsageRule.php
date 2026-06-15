<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Rule;

use Boundwize\Pyrameter\TestKind;

use function str_ends_with;
use function str_starts_with;

final readonly class UsageRule
{
    public function __construct(
        public string $classOrNamespace,
        public TestKind $kind,
    ) {
    }

    public function matches(string $consumedClass): bool
    {
        if ($consumedClass === $this->classOrNamespace) {
            return true;
        }

        if (! str_ends_with($this->classOrNamespace, '\\')) {
            return false;
        }

        return str_starts_with($consumedClass, $this->classOrNamespace);
    }
}
