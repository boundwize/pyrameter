<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Event;

use PHPUnit\Event\Code\Test as EventTest;

final readonly class IdOnlyEventCode extends EventTest
{
    /**
     * @param non-empty-string $identifier
     */
    public function __construct(
        private string $identifier,
    ) {
        parent::__construct(__FILE__);
    }

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return $this->identifier;
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->identifier;
    }
}
