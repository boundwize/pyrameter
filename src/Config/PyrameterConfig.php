<?php

declare(strict_types=1);

namespace Pyrameter\Config;

use InvalidArgumentException;
use mysqli;
use PDO;
use Pyrameter\Rule\UsageRule;
use Pyrameter\TestKind;

final class PyrameterConfig
{
    /**
     * @var list<UsageRule>
     */
    private array $usageRules = [];

    /**
     * @var array<string, array{min?: float, max?: float}>
     */
    private array $targets = [];

    private bool $warnOnly = true;

    public static function create(): self
    {
        return new self();
    }

    public static function defaults(): self
    {
        return self::create()
            ->usesClass(PDO::class, TestKind::Integration)
            ->usesClass(mysqli::class, TestKind::Integration)
            ->usesNamespace('Doctrine\DBAL\\', TestKind::Integration)
            ->usesNamespace('Doctrine\ORM\\', TestKind::Integration)
            ->usesNamespace('Doctrine\ODM\\', TestKind::Integration)
            ->usesNamespace('Redis\\', TestKind::Integration)
            ->usesNamespace('Predis\\', TestKind::Integration)
            ->usesNamespace('Symfony\Bundle\FrameworkBundle\Test\\', TestKind::Functional)
            ->usesNamespace('Symfony\Component\Panther\\', TestKind::E2E)
            ->usesNamespace('Facebook\WebDriver\\', TestKind::E2E)
            ->targetShape(
                unit: 70,
                functional: 18,
                integration: 8,
                e2e: 2,
                unknown: 2,
            )
            ->warnOnly();
    }

    /**
     * @param class-string $className
     */
    public function usesClass(string $className, TestKind $kind): self
    {
        $this->usageRules[] = new UsageRule(ltrim($className, '\\'), $kind);

        return $this;
    }

    public function usesNamespace(string $namespace, TestKind $kind): self
    {
        $this->usageRules[] = new UsageRule(ltrim($namespace, '\\'), $kind);

        return $this;
    }

    public function warnOnly(): self
    {
        $this->warnOnly = true;

        return $this;
    }

    public function targetShape(
        float $unit,
        float $functional,
        float $integration,
        float $e2e,
        float $unknown,
    ): self {
        $this->guardShapePercentage($unit, $functional, $integration, $e2e, $unknown);

        $this->targets = [
            TestKind::Unit->value => ['min' => $unit],
            TestKind::Functional->value => ['max' => $functional],
            TestKind::Integration->value => ['max' => $integration],
            TestKind::E2E->value => ['max' => $e2e],
            TestKind::Unknown->value => ['max' => $unknown],
        ];

        return $this;
    }

    /**
     * @return list<UsageRule>
     */
    public function usageRules(): array
    {
        return $this->usageRules;
    }

    /**
     * @return array<string, array{min?: float, max?: float}>
     */
    public function targetPercentages(): array
    {
        return $this->targets;
    }

    public function isWarnOnly(): bool
    {
        return $this->warnOnly;
    }

    private function guardShapePercentage(
        float $unit,
        float $functional,
        float $integration,
        float $e2e,
        float $unknown,
    ): void
    {
        foreach ([$unit, $functional, $integration, $e2e, $unknown] as $percentage) {
            if ($percentage < 0) {
                throw new InvalidArgumentException('Pyrameter target shape percentages must be zero or greater.');
            }
        }

        $total = $unit + $functional + $integration + $e2e + $unknown;

        if (abs($total - 100.0) > 0.00001) {
            throw new InvalidArgumentException(sprintf(
                'Pyrameter target shape percentages must total 100.0, %.1f given.',
                $total,
            ));
        }
    }
}
