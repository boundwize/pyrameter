# Pyrameter

<p align="center">
    <img src="./docs/assets/pyrameter_logo.png" alt="Pyrameter" width="160">
</p>

<p align="center">
    Keep your PHPUnit test suite shaped like a pyramid.
</p>

[![Latest Version](https://img.shields.io/github/release/boundwize/pyrameter.svg?style=flat-square)](https://github.com/boundwize/pyrameter/releases)
[![ci build](https://github.com/boundwize/pyrameter/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/boundwize/pyrameter/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/boundwize/pyrameter/branch/main/graph/badge.svg)](https://codecov.io/gh/boundwize/pyrameter)
[![PHPStan](https://img.shields.io/badge/style-level%20max-brightgreen.svg?style=flat-square&label=phpstan)](https://github.com/phpstan/phpstan)
[![Downloads](https://poser.pugx.org/boundwize/pyrameter/downloads)](https://packagist.org/packages/boundwize/pyrameter)

![Windows](https://img.shields.io/badge/Windows-supported-0078D6?logo=windows&logoColor=white&labelColor=555555)
![macOS](https://img.shields.io/badge/macOS-supported-C084FC?logo=apple&logoColor=white&labelColor=555555)
![Linux](https://img.shields.io/badge/Linux-supported-FCC624?logo=linux&logoColor=black&labelColor=555555)

```bash
vendor/bin/phpunit
........................
=========
Pyrameter
=========

Shape:  Integration Mountain
Result: Violated ⚠

               ▲  E2E             ✓
             ▄▄▄▄▄  Integration   ✗
           ▄▄▄▄▄▄▄▄▄  Functional  ✓
         ▄▄▄▄▄▄▄▄▄▄▄▄▄  Unit      ✗

+=============+=======+========+============+
|    KIND     | TESTS | ACTUAL |   TARGET   |
+=============+=======+========+============+
| Unit        |    39 |  65.0% | >= 70.0% ✗ |
+-------------+-------+--------+------------+
| Functional  |    10 |  16.7% | <= 18.0% ✓ |
+-------------+-------+--------+------------+
| Integration |    10 |  16.7% | <=  8.0% ✗ |
+-------------+-------+--------+------------+
| E2E         |     1 |   1.6% | <=  2.0% ✓ |
+-------------+-------+--------+------------+

Total: 60 tests

Your suite is getting heavier.
```

Pyrameter classifies executed tests as `unit`, `functional`, `integration`, or `e2e` from the code they use, then compares the totals with your target shape.

## Quick start

1. Install via `composer`:

```bash
composer require --dev boundwize/pyrameter
```

2. Register extension to `phpunit.xml`:

```xml
<extensions>
    <bootstrap class="Boundwize\Pyrameter\Extension"/>
</extensions>
```

3. Run PHPUnit as usual:

```bash
vendor/bin/phpunit
```

This uses the default rules and target shape.

## Configure

### Defaults or an empty config

Choose the starting point before adding rules:

| Start with | Behavior |
| --- | --- |
| `PyrameterConfig::defaults()` | Starts with built-in rules for common database, cache, filesystem, Symfony, CodeIgniter, Panther, and WebDriver usage, plus the default target shape. Rule calls add more rules; `targetShape()` replaces the default targets. |
| `PyrameterConfig::create()` | Starts with no rules and no targets. Only your chained rules classify tests as heavier than `unit`. |

Extend the built-in configuration:

```php
return PyrameterConfig::defaults()
    ->usesClass(App\Search\ExternalSearch::class, TestKind::Integration);
```

Define the complete configuration yourself:

```php
return PyrameterConfig::create()
    ->usesClass(PDO::class, TestKind::Integration)
    ->targetShape(
        unit: ['min' => 80],
        integration: ['max' => 20],
    );
```

A complete `pyrameter.php` can then combine rules, targets, and CI behavior:

```php
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::defaults()
    ->usesClass(App\Analyser\Analyser::class, TestKind::Integration)
    ->usesNamespace('App\Tests\Browser\\', TestKind::E2E)
    ->usesFunction('app_writes_to_disk', TestKind::Integration)
    ->targetShape(
        unit: ['min' => 75],
        functional: ['max' => 15],
        integration: ['max' => 7],
        e2e: ['max' => 2],
    )
    ->failOnViolation();
```

Rules can match a class or trait, a namespace prefix, or a function:

| Rule | Example |
| --- | --- |
| `usesClass()` | `->usesClass(PDO::class, TestKind::Integration)` |
| `usesNamespace()` | `->usesNamespace('App\Tests\Browser\\', TestKind::E2E)` |
| `usesFunction()` | `->usesFunction('file_put_contents', TestKind::Integration)` |

### Rule exceptions

Use `unless` to ignore a rule when the test also consumes another class:

```php
use App\Tests\Concerns\InteractsWithDatabase;
use App\Tests\Concerns\MakesHttpRequests;

return PyrameterConfig::create()
    ->usesClass(
        InteractsWithDatabase::class,
        TestKind::Integration,
        unless: [MakesHttpRequests::class],
    )
    ->usesClass(MakesHttpRequests::class, TestKind::Functional);
```

| Traits used by the test | Result |
| --- | --- |
| `InteractsWithDatabase` | `integration` |
| `MakesHttpRequests` | `functional` |
| Both traits | `functional` |

The optional `unless` argument is also available on `usesNamespace()` and `usesFunction()`.

The equivalent CodeIgniter exception is already included in the defaults:

```php
return PyrameterConfig::defaults();
```

Tests using only `DatabaseTestTrait` are `integration`; tests that also use `ControllerTestTrait` remain `functional`.

Load a config from another path:

```xml
<extensions>
    <bootstrap class="Boundwize\Pyrameter\Extension">
        <parameter name="config" value="config/pyrameter.php"/>
    </bootstrap>
</extensions>
```

## Classification

| Usage | Kind |
| --- | --- |
| No configured heavy usage | `unit` |
| Framework test runtime | `functional` |
| Database, cache, queue, filesystem, or external boundary | `integration` |
| Browser driver usage | `e2e` |

- The heaviest matching rule wins.
- Mocked dependencies do not trigger a rule by themselves.
- Data-provider datasets are counted separately.

## Targets and CI

```php
return PyrameterConfig::defaults()
    ->targetShape(
        unit: ['min' => 70],
        functional: ['max' => 20],
        integration: ['max' => 8],
        e2e: ['max' => 2],
    )
    ->failOnViolation();
```

Targets are percentages. An omitted `min` is `0`; an omitted `max` is `100`. Without `failOnViolation()`, Pyrameter only reports violations.
