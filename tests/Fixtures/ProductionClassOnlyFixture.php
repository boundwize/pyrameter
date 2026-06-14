<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

final class ProductionClassOnlyFixture extends TestCase
{
    public function test_it_references_a_project_class_that_uses_pdo_internally(): void
    {
        self::assertSame(ProductionUsesPdo::class, ProductionUsesPdo::class);
    }
}
