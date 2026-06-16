# Pyrameter

<p align="center">
    <img src="./docs/assets/pyrameter_logo.png" alt="Pyrameter" width="300">
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

Pyrameter is a PHPUnit extension that shows what your test suite is becoming. It classifies each PHPUnit test execution by the classes and namespaces the test file consumes, then prints a shape report after PHPUnit runs.

Use it to spot a suite that is getting heavier, agree on what "healthy" means for your project, and optionally fail CI when the pyramid drifts too far.

```bash
vendor/bin/phpunit
........................
Pyrameter
=========

Shape: Integration Mountain
Result: Violated ⚠

              ╭───────╮
              │ E2E ✓ │
          ╭───┴───────┴───╮
          │ Integration ✗ │
       ╭──┴───────────────┴──╮
       │    Functional ✓     │
   ╭───┴─────────────────────┴───╮
   │           Unit ✗            │
   ╰─────────────────────────────╯

┌──────┬───────┬────────┬────────────┐
│ KIND │ TESTS │ ACTUAL │   TARGET   │
╞══════╪═══════╪════════╪════════════╡
│ Unit │    39 │  65.0% │ >= 70.0% ✗ │
├──────┼───────┼────────┼────────────┤
│ Func │    10 │  16.7% │ <= 18.0% ✓ │
├──────┼───────┼────────┼────────────┤
│ Int  │    10 │  16.7% │ <=  8.0% ✗ │
├──────┼───────┼────────┼────────────┤
│ E2E  │     1 │   1.6% │ <=  2.0% ✓ │
└──────┴───────┴────────┴────────────┘

Total: 60 tests

Your suite is getting heavier.
```

## How it works

Pyrameter does not trust test directories, and it does not scan production classes. Instead, it classifies by configured class or namespace usage in test files:

- no configured heavy usage => unit
- framework test runtime => functional
- real resource boundary, such as database, cache, queue, filesystem, or external service => integration
- browser driver usage => e2e

When multiple usages match, the heaviest kind wins. Mocked heavy dependencies stay unit.

Counts match PHPUnit's test execution count. Each data-provider dataset is counted separately, while all datasets for the same test method usually share the same classification because Pyrameter classifies from the test file's consumed classes.

Your pyramid, your rules: decide which class usage means functional or integration in your project, then configure Pyrameter to match your team's belief.

For example, if a test consumes an analyser that reads real paths, configure that analyser class or namespace as integration.

## Quick start

Pyrameter supports PHP 8.2+ and PHPUnit 11 or 12.

Install it as a dev dependency:

```bash
composer require --dev boundwize/pyrameter
```

Register the PHPUnit extension into `phpunit.xml`:

```xml
<extensions>
    <bootstrap class="Boundwize\Pyrameter\Extension"/>
</extensions>
```

Run PHPUnit as usual:

```bash
vendor/bin/phpunit
```

If the `config` parameter is omitted, Pyrameter looks for `pyrameter.php` in the current working directory. If the file does not exist, it uses the default rules and target shape.

## Configure

Create `pyrameter.php` when you want to tune the rules or targets.

Start with `defaults()` to keep Pyrameter's built-in rules for PDO, mysqli, Doctrine, Redis, Symfony functional tests, Panther, and WebDriver, then add your project-specific beliefs:

```php
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::defaults()
    ->usesClass(App\Analyser\Analyser::class, TestKind::Integration)
    ->usesNamespace('App\Tests\Browser\\', TestKind::E2E)
    ->targetShape(
        unit: ['min' => 75],
        functional: ['max' => 15],
        integration: ['max' => 7],
        e2e: ['max' => 2],
    );
```

Use `create()` when you want full control. It starts with no usage rules and no target shape:

```php
<?php

declare(strict_types=1);

use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\TestKind;

return PyrameterConfig::create()
    ->usesClass(PDO::class, TestKind::Integration)
    ->usesNamespace('Doctrine\DBAL\\', TestKind::Integration)
    ->usesNamespace('Symfony\Bundle\FrameworkBundle\Test\\', TestKind::Functional)
    ->usesNamespace('Symfony\Component\Panther\\', TestKind::E2E)
    ->usesNamespace('Facebook\WebDriver\\', TestKind::E2E)

    ->targetShape(
        unit: ['min' => 70],
        functional: ['max' => 18],
        integration: ['max' => 8],
        e2e: ['max' => 2],
    );
```

Targets are percentage ranges. Missing `min` means `0`; missing `max` means `100`. When `targetShape()` is called, missing kinds default to `['min' => 0, 'max' => 100]`, which Pyrameter reports as no target.

## Fail CI

By default, Pyrameter is report-only. It prints target violations without changing PHPUnit's exit code.

Turn violations into a failing PHPUnit process when you are ready to enforce the shape:

```php
return PyrameterConfig::defaults()
    ->failOnViolation();
```

## A note on taxonomy

Pyrameter measures suite shape from static usage rules in test files. It is a useful pressure gauge, not a perfect taxonomy judge.
