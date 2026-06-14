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
    ->targets()
        ->unit(min: 70)
        ->functional(max: 20)
        ->integration(max: 16)
        ->e2e(max: 2)
        ->unknown(max: 2)
    ->warnOnly();
