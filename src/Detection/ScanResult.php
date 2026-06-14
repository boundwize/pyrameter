<?php

declare(strict_types=1);

namespace Pyrameter\Detection;

final readonly class ScanResult
{
    /**
     * @param list<string> $consumedClasses
     */
    private function __construct(
        public bool $inspectable,
        public array $consumedClasses,
        public ?string $errorMessage = null,
    ) {
    }

    /**
     * @param list<string> $consumedClasses
     */
    public static function inspectable(array $consumedClasses): self
    {
        return new self(true, $consumedClasses);
    }

    public static function unknown(string $errorMessage): self
    {
        return new self(false, [], $errorMessage);
    }
}
