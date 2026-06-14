<?php

declare(strict_types=1);

namespace Pyrameter\Config;

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
            ->targets()
                ->unit(min: 70)
                ->functional(max: 20)
                ->integration(max: 8)
                ->e2e(max: 2)
                ->unknown(max: 2)
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

    public function targets(): self
    {
        return $this;
    }

    public function unit(?float $min = null, ?float $max = null): self
    {
        return $this->target(TestKind::Unit, $min, $max);
    }

    public function functional(?float $min = null, ?float $max = null): self
    {
        return $this->target(TestKind::Functional, $min, $max);
    }

    public function integration(?float $min = null, ?float $max = null): self
    {
        return $this->target(TestKind::Integration, $min, $max);
    }

    public function e2e(?float $min = null, ?float $max = null): self
    {
        return $this->target(TestKind::E2E, $min, $max);
    }

    public function unknown(?float $min = null, ?float $max = null): self
    {
        return $this->target(TestKind::Unknown, $min, $max);
    }

    public function warnOnly(): self
    {
        $this->warnOnly = true;

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

    private function target(TestKind $kind, ?float $min, ?float $max): self
    {
        $target = [];

        if ($min !== null) {
            $target['min'] = (float) $min;
        }

        if ($max !== null) {
            $target['max'] = (float) $max;
        }

        $this->targets[$kind->value] = $target;

        return $this;
    }
}
