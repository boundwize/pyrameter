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

    public static function fromTarget(TestKind $kind, float $actual, float $min, float $max): self
    {
        $passed = true;

        if ($actual < $min) {
            $passed = false;
        }

        if ($actual > $max) {
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
            return 'No target';
        }

        if ($this->min !== null && $this->min > 0.0 && $this->max !== null && $this->max < 100.0) {
            return sprintf('%4.1f%%-%4.1f%%', $this->min, $this->max);
        }

        if ($this->min !== null && $this->min > 0.0) {
            return sprintf('>= %4.1f%%', $this->min);
        }

        if ($this->max !== null && $this->max < 100.0) {
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
