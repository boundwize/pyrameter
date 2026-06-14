<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Fixtures;

use PDO;

final class ProductionUsesPdo
{
    public function connection(): PDO
    {
        return new PDO('sqlite::memory:');
    }
}
