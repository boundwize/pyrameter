<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter;

enum TestKind: string
{
    case Unit        = 'unit';
    case Functional  = 'functional';
    case Integration = 'integration';
    case E2E         = 'e2e';
    case Unknown     = 'unknown';

    public function weight(): int
    {
        return match ($this) {
            self::Unknown => 0,
            self::Unit => 1,
            self::Functional => 2,
            self::Integration => 3,
            self::E2E => 4,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unit',
            self::Functional => 'Functional',
            self::Integration => 'Integration',
            self::E2E => 'E2E',
            self::Unknown => 'Unknown',
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Unit,
            self::Functional,
            self::Integration,
            self::E2E,
            self::Unknown,
        ];
    }
}
