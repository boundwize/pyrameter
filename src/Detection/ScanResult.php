<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

final readonly class ScanResult
{
    /**
     * @param list<string> $consumedUsages Consumed class-like and function usages.
     */
    private function __construct(
        public bool $inspectable,
        public array $consumedUsages,
        public ?string $errorMessage = null,
    ) {
    }

    /**
     * @param list<string> $consumedUsages
     */
    public static function inspectable(array $consumedUsages): self
    {
        return new self(true, $consumedUsages);
    }

    public static function uninspectable(string $errorMessage): self
    {
        return new self(false, [], $errorMessage);
    }
}
