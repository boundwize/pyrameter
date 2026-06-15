<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::create()
    ->usesClass(PDO::class, TestKind::Integration)
    ->targetShape(
        unit: ['min' => 100],
    )
    ->failOnViolation();
