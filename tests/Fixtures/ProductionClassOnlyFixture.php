<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

final class ProductionClassOnlyFixture extends TestCase
{
    public function testItReferencesAProjectClassThatUsesPdoInternally(): void
    {
        $this->assertSame(ProductionUsesPdo::class, ProductionUsesPdo::class);
    }
}
