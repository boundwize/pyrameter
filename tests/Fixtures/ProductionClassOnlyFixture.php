<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

final class ProductionClassOnlyFixture extends TestCase
{
    public function testItReferencesAProjectClassThatUsesPdoInternally(): void
    {
        self::assertSame(ProductionUsesPdo::class, ProductionUsesPdo::class);
    }
}
