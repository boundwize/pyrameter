<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

use Boundwize\Pyrameter\Rule\UsageType;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\UnionType;
use PhpParser\NodeVisitorAbstract;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

use function array_keys;
use function ltrim;
use function sprintf;
use function strtolower;

final class ConsumedUsageVisitor extends NodeVisitorAbstract
{
    /** @var array<string, true> */
    private array $consumedUsages = [];

    /** @var array<string, true> */
    private array $typeOnlyUsages = [];

    /** @var array<string, true> */
    private array $mockTargetUsages = [];

    /** @var array<string, true> */
    private const MOCK_RESULT_TYPES = [
        MockObject::class => true,
        Stub::class       => true,
    ];

    /** @var array<string, true> */
    private const MOCK_METHODS = [
        'createMock'           => true,
        'createStub'           => true,
        'createConfiguredMock' => true,
        'createPartialMock'    => true,
        'getMockBuilder'       => true,
    ];

    /** @var array<string, true> */
    private const RESERVED_CLASS_NAMES = [
        'self'   => true,
        'static' => true,
        'parent' => true,
    ];

    public function enterNode(Node $node): null
    {
        if ($node instanceof Class_) {
            $this->addName($node->extends);

            foreach ($node->implements as $interface) {
                $this->addName($interface);
            }
        }

        if ($node instanceof Interface_) {
            foreach ($node->extends as $interface) {
                $this->addName($interface);
            }
        }

        if ($node instanceof TraitUse) {
            foreach ($node->traits as $trait) {
                $this->addName($trait);
            }
        }

        if ($node instanceof New_ && $node->class instanceof Name) {
            $this->addName($node->class);
        }

        if ($node instanceof Instanceof_ && $node->class instanceof Name) {
            $this->addName($node->class);
        }

        if ($node instanceof Catch_) {
            foreach ($node->types as $type) {
                $this->addName($type);
            }
        }

        if (
            $node instanceof ClassConstFetch
            && $node->class instanceof Name
            && $this->isClassConstant($node)
        ) {
            if ($node->getAttribute('isMockTarget', false) === true) {
                $this->addMockTarget($node->class);
            } else {
                $this->addName($node->class);
            }
        }

        if ($node instanceof MethodCall || $node instanceof StaticCall) {
            $this->markMockTarget($node);
        }

        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $this->addName($node->class);
        }

        if ($node instanceof StaticPropertyFetch && $node->class instanceof Name) {
            $this->addName($node->class);
        }

        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $this->addFunctionName($node->name);
        }

        if ($node instanceof Param) {
            $this->addType($node->type);
        }

        if ($node instanceof Property) {
            $this->addType($node->type);
        }

        if ($node instanceof FunctionLike) {
            $this->addType($node->getReturnType());
        }

        if ($node instanceof Attribute) {
            $this->addName($node->name);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function consumedUsages(): array
    {
        $consumedUsages = $this->consumedUsages;

        // A mocked class only referenced by type declarations (`private PDO&MockObject $pdo`)
        // is not a real dependency of the test, so it must not trigger heavier rules.
        foreach (array_keys($this->mockTargetUsages) as $usageKey) {
            if (isset($this->typeOnlyUsages[$usageKey])) {
                unset($consumedUsages[$usageKey]);
            }
        }

        return array_keys($consumedUsages);
    }

    public function reset(): void
    {
        $this->consumedUsages   = [];
        $this->typeOnlyUsages   = [];
        $this->mockTargetUsages = [];
    }

    private function addType(null|Identifier|Name|ComplexType $type): void
    {
        if ($type instanceof Name) {
            $this->addName($type, fromType: true);
            return;
        }

        if ($type instanceof NullableType) {
            $this->addType($type->type);
            return;
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            if ($type instanceof IntersectionType && $this->intersectsMockResultType($type)) {
                foreach ($type->types as $innerType) {
                    if ($innerType instanceof Name && ! $this->isMockResultType($innerType)) {
                        $this->addMockTarget($innerType);
                    }
                }
            }

            foreach ($type->types as $innerType) {
                $this->addType($innerType);
            }
        }
    }

    private function addName(?Name $name, bool $fromType = false): void
    {
        if (! $name instanceof Name) {
            return;
        }

        // NameResolver runs with replaceNodes = true, so names are already resolved
        // in place and toString() yields the fully-qualified name.
        $this->addUsage($name->toString(), $fromType);
    }

    private function addMockTarget(Name $name): void
    {
        $usage = ltrim($name->toString(), '\\');

        if ($usage === '') {
            return;
        }

        $this->mockTargetUsages[$this->usageKey(UsageType::ClassLike, $usage)] = true;
    }

    private function intersectsMockResultType(IntersectionType $intersectionType): bool
    {
        foreach ($intersectionType->types as $type) {
            if ($type instanceof Name && $this->isMockResultType($type)) {
                return true;
            }
        }

        return false;
    }

    private function isMockResultType(Name $name): bool
    {
        return isset(self::MOCK_RESULT_TYPES[ltrim($name->toString(), '\\')]);
    }

    private function addFunctionName(Name $name): void
    {
        $functionName = strtolower(ltrim($name->toString(), '\\'));

        if ($functionName === '') {
            return;
        }

        $this->consumedUsages[$this->usageKey(UsageType::Function, $functionName)] = true;
    }

    private function addUsage(string $usage, bool $fromType = false): void
    {
        $usage = ltrim($usage, '\\');

        if ($usage === '' || isset(self::RESERVED_CLASS_NAMES[strtolower($usage)])) {
            return;
        }

        $usageKey = $this->usageKey(UsageType::ClassLike, $usage);

        if ($fromType) {
            if (! isset($this->consumedUsages[$usageKey])) {
                $this->typeOnlyUsages[$usageKey] = true;
            }
        } else {
            unset($this->typeOnlyUsages[$usageKey]);
        }

        $this->consumedUsages[$usageKey] = true;
    }

    private function usageKey(UsageType $usageType, string $usage): string
    {
        return sprintf('%s:%s', $usageType->value, $usage);
    }

    private function isClassConstant(ClassConstFetch $classConstFetch): bool
    {
        return $classConstFetch->name instanceof Identifier
            && strtolower($classConstFetch->name->toString()) === 'class';
    }

    private function markMockTarget(MethodCall|StaticCall $call): void
    {
        if (! $call->name instanceof Identifier || ! isset(self::MOCK_METHODS[$call->name->toString()])) {
            return;
        }

        $firstArg = $call->args[0] ?? null;

        if ($firstArg instanceof Arg && $firstArg->value instanceof ClassConstFetch) {
            $firstArg->value->setAttribute('isMockTarget', true);
        }
    }
}
