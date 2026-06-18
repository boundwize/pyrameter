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

Pyrameter is a PHPUnit extension that reports the shape of your test suite after PHPUnit runs. It classifies each executed test as `unit`, `functional`, `integration`, or `e2e`, compares the totals with your target shape, and can fail CI when the suite drifts too far.

It works from the classes and namespaces used by your test files, so you can define what counts as "heavy" in your project instead of relying on test directory names.

## Quick start

Install Pyrameter as a dev dependency:

```bash
composer require --dev boundwize/pyrameter
```

Register the extension in `phpunit.xml`:

```xml
<extensions>
    <bootstrap class="Boundwize\Pyrameter\Extension"/>
</extensions>
```

Run PHPUnit:

```bash
vendor/bin/phpunit
```

Without extra configuration, Pyrameter uses its default rules and target shape. If a `pyrameter.php` file exists in the current working directory, it is loaded automatically.

## Configure

Create `pyrameter.php` when you want to tune classification rules, target percentages, or CI behavior:

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
    );
```

`PyrameterConfig::defaults()` includes rules for common database usage (including CodeIgniter database tests),
cache, filesystem, Symfony and CodeIgniter controller functional tests, Panther, and WebDriver usage.
CodeIgniter tests using both `ControllerTestTrait` and `DatabaseTestTrait` remain functional; database-only tests
are integration tests.

Use `PyrameterConfig::create()` instead when you want to start with no rules or targets and define everything yourself:

```php
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::create()
    ->usesClass(PDO::class, TestKind::Integration)
    ->usesNamespace('App\Controller\\', TestKind::Functional)
    ->usesNamespace('App\Browser\\', TestKind::E2E)
    ->usesFunction('file_put_contents', TestKind::Integration)
    ->targetShape(
        unit: ['min' => 70],
        functional: ['max' => 20],
        integration: ['max' => 8],
        e2e: ['max' => 2],
    );
```

With `create()`, only the rules you add are used for heavier classifications; all other executed tests stay `unit`.

Use `usesClass()` for a specific class, `usesNamespace()` for a namespace prefix, and `usesFunction()` for a function call that should classify a test as a heavier kind. Names can be configured with or without a leading backslash.

To load a config file from another path, pass the `config` parameter to the PHPUnit extension:

```xml
<extensions>
    <bootstrap class="Boundwize\Pyrameter\Extension">
        <parameter name="config" value="config/pyrameter.php"/>
    </bootstrap>
</extensions>
```

## Classification

Pyrameter scans the consumed classes, namespaces, and function calls in each test file:

| Usage | Kind |
| --- | --- |
| No configured heavy usage | `unit` |
| Framework test runtime | `functional` |
| Database, cache, queue, filesystem, or external boundary | `integration` |
| Browser driver usage | `e2e` |

When multiple rules match, the heaviest kind wins. Mocked heavy dependencies stay `unit` unless the test file also consumes a class or namespace you configured as heavier.

Counts follow PHPUnit's executed test count, so data-provider datasets are counted separately.

## Targets and CI

Targets are percentages. Missing `min` means `0`; missing `max` means `100`.

By default, Pyrameter is report-only. To fail PHPUnit when the target shape is violated, enable `failOnViolation()`:

```php
return PyrameterConfig::defaults()
    ->failOnViolation();
```

Pyrameter is a pressure gauge for suite shape, not a perfect taxonomy judge. Tune the rules to match how your team defines unit, functional, integration, and e2e tests.
