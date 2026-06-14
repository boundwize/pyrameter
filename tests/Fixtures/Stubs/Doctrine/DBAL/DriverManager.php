<?php

declare(strict_types=1);

namespace Doctrine\DBAL;

use stdClass;

final class DriverManager
{
    /**
     * @param array<string, mixed> $params
     */
    public static function getConnection(array $params): object
    {
        return new stdClass();
    }
}
