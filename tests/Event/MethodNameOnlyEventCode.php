<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Event;

use PHPUnit\Event\Code\Test as EventTest;

final readonly class MethodNameOnlyEventCode extends EventTest
{
    /**
     * @param class-string $className
     * @param non-empty-string $methodName
     */
    public function __construct(
        private string $className,
        private string $methodName,
    ) {
        parent::__construct(__FILE__);
    }

    /**
     * @return non-empty-string
     */
    public function id(): string
    {
        return 'legacy-event-without-test-id';
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->methodName;
    }

    /**
     * @return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    /**
     * @return non-empty-string
     */
    public function methodName(): string
    {
        return $this->methodName;
    }
}
