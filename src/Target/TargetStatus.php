<?php

declare(strict_types=1);

namespace Pyrameter\Target;

use Pyrameter\TestKind;

final readonly class TargetStatus
{
    private function __construct(
        public TestKind $kind,
        public float $actual,
        public ?float $min,
        public ?float $max,
        public bool $passed,
        public bool $ignored,
    ) {
    }

    public static function fromTarget(TestKind $kind, float $actual, ?float $min, ?float $max): self
    {
        $passed = true;

        if ($min !== null && $actual < $min) {
            $passed = false;
        }

        if ($max !== null && $actual > $max) {
            $passed = false;
        }

        return new self($kind, $actual, $min, $max, $passed, false);
    }

    public static function ignored(TestKind $kind, float $actual): self
    {
        return new self($kind, $actual, null, null, true, true);
    }

    public function label(): string
    {
        if ($this->ignored) {
            return '-';
        }

        if ($this->min !== null) {
            return sprintf('>= %4.1f%%', $this->min);
        }

        if ($this->max !== null) {
            return sprintf('<= %4.1f%%', $this->max);
        }

        return '-';
    }

    public function symbol(): string
    {
        if ($this->ignored) {
            return '-';
        }

        return $this->passed ? '✓' : '✗';
    }
}
