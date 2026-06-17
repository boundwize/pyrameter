<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Config;

use Boundwize\Pyrameter\Rule\UsageRule;
use Boundwize\Pyrameter\TestKind;
use InvalidArgumentException;
use mysqli;
use PDO;

use function ltrim;
use function rtrim;
use function sprintf;

final class PyrameterConfig
{
    /** @var list<non-empty-string> */
    private const FILE_OPERATION_FUNCTIONS = [
        'file_get_contents',
        'file_put_contents',
        'fopen',
        'fread',
        'fwrite',
        'fgets',
        'fgetc',
        'fclose',
        'feof',
        'rewind',
        'fseek',
        'ftell',
        'fflush',
        'ftruncate',
        'file_exists',
        'is_file',
        'is_dir',
        'is_readable',
        'is_writable',
        'is_executable',
        'filesize',
        'filemtime',
        'filectime',
        'fileatime',
        'fileperms',
        'fileowner',
        'filegroup',
        'filetype',
        'stat',
        'lstat',
        'touch',
        'copy',
        'rename',
        'unlink',
        'mkdir',
        'rmdir',
        'opendir',
        'readdir',
        'closedir',
        'rewinddir',
        'scandir',
        'glob',
        'chdir',
        'getcwd',
        'realpath',
        'chmod',
        'chown',
        'chgrp',
        'umask',
        'link',
        'symlink',
        'readlink',
        'is_link',
        'tmpfile',
        'tempnam',
        'flock',
        'is_uploaded_file',
        'move_uploaded_file',
        'fgetcsv',
        'fputcsv',
        'parse_ini_file',
    ];

    /** @var list<UsageRule> */
    private array $usageRules = [];

    /** @var array<string, array{min: float, max: float}> */
    private array $targets = [];

    private bool $failOnViolation = false;

    public static function create(): self
    {
        return new self();
    }

    public static function defaults(): self
    {
        $pyrameterConfig = self::create()
            ->usesClass(PDO::class, TestKind::Integration)
            ->usesClass(mysqli::class, TestKind::Integration)
            ->usesNamespace('Doctrine\DBAL\\', TestKind::Integration)
            ->usesNamespace('Doctrine\ORM\\', TestKind::Integration)
            ->usesNamespace('Doctrine\ODM\\', TestKind::Integration)
            ->usesClass('Redis', TestKind::Integration)
            ->usesClass('RedisCluster', TestKind::Integration)
            ->usesClass('RedisSentinel', TestKind::Integration)
            ->usesNamespace('Predis\\', TestKind::Integration)
            ->usesNamespace('Symfony\Bundle\FrameworkBundle\Test\\', TestKind::Functional)
            ->usesNamespace('Symfony\Component\Panther\\', TestKind::E2E)
            ->usesNamespace('Facebook\WebDriver\\', TestKind::E2E);

        foreach (self::FILE_OPERATION_FUNCTIONS as $functionName) {
            $pyrameterConfig->usesFunction($functionName, TestKind::Integration);
        }

        return $pyrameterConfig->targetShape(
            unit: ['min' => 70],
            functional: ['max' => 18],
            integration: ['max' => 8],
            e2e: ['max' => 2],
        );
    }

    /**
     * @param class-string $className
     */
    public function usesClass(string $className, TestKind $testKind): self
    {
        $this->usageRules[] = new UsageRule(ltrim($className, '\\'), $testKind);

        return $this;
    }

    public function usesNamespace(string $namespace, TestKind $testKind): self
    {
        $this->usageRules[] = new UsageRule(rtrim(ltrim($namespace, '\\'), '\\') . '\\', $testKind);

        return $this;
    }

    public function usesFunction(string $functionName, TestKind $testKind): self
    {
        $this->usageRules[] = new UsageRule(ltrim($functionName, '\\'), $testKind, caseInsensitive: true);

        return $this;
    }

    /**
     * @param array{min?: float|int, max?: float|int} $unit
     * @param array{min?: float|int, max?: float|int} $functional
     * @param array{min?: float|int, max?: float|int} $integration
     * @param array{min?: float|int, max?: float|int} $e2e
     */
    public function targetShape(
        array $unit = [],
        array $functional = [],
        array $integration = [],
        array $e2e = [],
    ): self {
        $this->targets = [
            TestKind::Unit->value        => $this->normalizeTarget(TestKind::Unit, $unit),
            TestKind::Functional->value  => $this->normalizeTarget(TestKind::Functional, $functional),
            TestKind::Integration->value => $this->normalizeTarget(TestKind::Integration, $integration),
            TestKind::E2E->value         => $this->normalizeTarget(TestKind::E2E, $e2e),
        ];

        $this->guardShapeFeasibility($this->targets);

        return $this;
    }

    public function failOnViolation(): self
    {
        $this->failOnViolation = true;

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
     * @return array<string, array{min: float, max: float}>
     */
    public function targetPercentages(): array
    {
        return $this->targets;
    }

    public function shouldFailOnViolation(): bool
    {
        return $this->failOnViolation;
    }

    /**
     * @param array{min?: float|int, max?: float|int} $target
     * @return array{min: float, max: float}
     */
    private function normalizeTarget(TestKind $testKind, array $target): array
    {
        $min = (float) ($target['min'] ?? 0);
        $max = (float) ($target['max'] ?? 100);

        if ($min < 0 || $max < 0) {
            throw new InvalidArgumentException(sprintf(
                'Pyrameter target shape percentages for %s must be zero or greater.',
                $testKind->label(),
            ));
        }

        if ($min > 100 || $max > 100) {
            throw new InvalidArgumentException(sprintf(
                'Pyrameter target shape percentages for %s must be 100 or less.',
                $testKind->label(),
            ));
        }

        if ($min > $max) {
            throw new InvalidArgumentException(sprintf(
                'Pyrameter target shape minimum for %s cannot be greater than its maximum.',
                $testKind->label(),
            ));
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * @param array<string, array{min: float, max: float}> $targets
     */
    private function guardShapeFeasibility(array $targets): void
    {
        $minimumTotal = 0.0;
        $maximumTotal = 0.0;

        foreach ($targets as $target) {
            $minimumTotal += $target['min'];
            $maximumTotal += $target['max'];
        }

        if ($minimumTotal > 100.0) {
            throw new InvalidArgumentException(sprintf(
                'Pyrameter target shape minimum percentages cannot exceed 100.0, %.1f given.',
                $minimumTotal,
            ));
        }

        if ($maximumTotal < 100.0) {
            throw new InvalidArgumentException(sprintf(
                'Pyrameter target shape maximum percentages must allow 100.0, %.1f given.',
                $maximumTotal,
            ));
        }
    }
}
