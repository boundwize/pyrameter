<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Config;

use Boundwize\Pyrameter\Config\PyrameterConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PyrameterConfigTest extends TestCase
{
    public function testTargetShapePercentagesMustNotBeNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be zero or greater');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => -1],
        );
    }

    public function testTargetShapePercentagesMustNotExceedOneHundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be 100 or less');

        PyrameterConfig::create()->targetShape(
            unit: ['max' => 101],
        );
    }

    public function testTargetShapeMinimumTotalCannotExceedOneHundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('minimum percentages cannot exceed 100.0');

        PyrameterConfig::create()->targetShape(
            unit: ['min' => 80],
            functional: ['min' => 30],
        );
    }
}
