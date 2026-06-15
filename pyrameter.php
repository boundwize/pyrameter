<?php

declare(strict_types=1);

use Pyrameter\Config\PyrameterConfig;
use Pyrameter\TestKind;

return PyrameterConfig::defaults()
    ->usesNamespace('Pyrameter\\Event\\', TestKind::Functional)
    ->targetShape(
        unit: ['min' => 60],
        functional: ['max' => 20],
        integration: ['max' => 16],
        e2e: ['max' => 2],
        unknown: ['max' => 2],
    )
    ->failOnViolation();
