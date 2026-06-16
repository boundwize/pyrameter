<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::defaults()
    ->usesNamespace('Boundwize\\Pyrameter\\Event\\', TestKind::Functional)
    ->targetShape(
        unit: ['min' => 60],
        functional: ['max' => 20],
        integration: ['max' => 16],
        e2e: ['max' => 2],
    )
    ->failOnViolation();
