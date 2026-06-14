<?php

declare(strict_types=1);

namespace Pyrameter\Report;

final readonly class SuiteShape
{
    public function __construct(
        public string $name,
        public string $verdict,
        public bool $healthy,
    ) {
    }
}
