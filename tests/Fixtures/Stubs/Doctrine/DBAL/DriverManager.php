<?php

declare(strict_types=1);

namespace Doctrine\DBAL;

use stdClass;

final class DriverManager
{
    public static function getConnection(): stdClass
    {
        return new stdClass();
    }
}
