<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Target;

use Boundwize\Pyrameter\TestKind;

use function sprintf;

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

    public static function fromTarget(TestKind $testKind, float $actual, float $min, float $max): self
    {
        $passed = true;

        if ($actual < $min) {
            $passed = false;
        }

        if ($actual > $max) {
            $passed = false;
        }

        return new self($testKind, $actual, $min, $max, $passed, false);
    }

    public static function ignored(TestKind $testKind, float $actual): self
    {
        return new self($testKind, $actual, null, null, true, true);
    }

    public function label(): string
    {
        if ($this->ignored) {
            return 'No target';
        }

        if ($this->min !== null && $this->min > 0.0 && $this->max !== null && $this->max < 100.0) {
            return sprintf('%.1f%%-%.1f%%', $this->min, $this->max);
        }

        if ($this->min !== null && $this->min > 0.0) {
            return sprintf('>= %.1f%%', $this->min);
        }

        if ($this->max !== null && $this->max < 100.0) {
            return sprintf('<= %.1f%%', $this->max);
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
