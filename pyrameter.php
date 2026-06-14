<?php

declare(strict_types=1);

use Pyrameter\Config\PyrameterConfig;
use Pyrameter\TestKind;

return PyrameterConfig::create()
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
        unit: 60,
        functional: 20,
        integration: 16,
        e2e: 2,
        unknown: 2,
    );
