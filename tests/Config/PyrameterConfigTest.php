<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Config;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pyrameter\Config\PyrameterConfig;

final class PyrameterConfigTest extends TestCase
{
    public function test_target_shape_percentages_must_not_be_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be zero or greater');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => -1],
        );
    }

    public function test_target_shape_percentages_must_not_exceed_one_hundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be 100 or less');

        PyrameterConfig::create()->targetShape(
            unit: ['max' => 101],
        );
    }

    public function test_target_shape_minimum_total_cannot_exceed_one_hundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('minimum percentages cannot exceed 100.0');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => 80],
            functional: ['min' => 30],
        );
    }
}
