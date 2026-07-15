<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::defaults()
    ->usesNamespace('Boundwize\\Pyrameter\\Event\\', TestKind::Functional)
    ->targetShape(
        unit: ['min' => 76],
        functional: ['max' => 8],
        integration: ['max' => 16],
        e2e: ['max' => 0],
    )
    ->failOnViolation();
