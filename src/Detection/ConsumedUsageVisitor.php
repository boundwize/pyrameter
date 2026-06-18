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

use function array_keys;
use function in_array;
use function ltrim;
use function sprintf;
use function strtolower;

final class ConsumedUsageVisitor extends NodeVisitorAbstract
{
    /** @var array<string, true> */
    private array $consumedUsages = [];

    /** @var list<string> */
    private array $mockMethods = [
        'createMock',
        'createStub',
        'createConfiguredMock',
        'createPartialMock',
        'getMockBuilder',
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
            && $node->getAttribute('isMockTarget', false) !== true
        ) {
            $this->addName($node->class);
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
        return array_keys($this->consumedUsages);
    }

    public function reset(): void
    {
        $this->consumedUsages = [];
    }

    private function addType(null|Identifier|Name|ComplexType $type): void
    {
        if ($type instanceof Name) {
            $this->addName($type);
            return;
        }

        if ($type instanceof NullableType) {
            $this->addType($type->type);
            return;
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            foreach ($type->types as $innerType) {
                $this->addType($innerType);
            }
        }
    }

    private function addName(?Name $name): void
    {
        if (! $name instanceof Name) {
            return;
        }

        $resolvedName = $name->getAttribute('resolvedName');
        $className    = $resolvedName instanceof Name ? $resolvedName->toString() : $name->toString();

        $this->addUsage($className);
    }

    private function addFunctionName(Name $name): void
    {
        $functionName = strtolower(ltrim($name->toString(), '\\'));

        if ($functionName === '') {
            return;
        }

        $this->consumedUsages[$this->usageKey(UsageType::Function, $functionName)] = true;
    }

    private function addUsage(string $usage): void
    {
        $usage = ltrim($usage, '\\');

        if ($usage === '' || in_array(strtolower($usage), ['self', 'static', 'parent'], true)) {
            return;
        }

        $this->consumedUsages[$this->usageKey(UsageType::ClassLike, $usage)] = true;
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
        if (! $call->name instanceof Identifier || ! in_array($call->name->toString(), $this->mockMethods, true)) {
            return;
        }

        $firstArg = $call->args[0] ?? null;

        if ($firstArg instanceof Arg && $firstArg->value instanceof ClassConstFetch) {
            $firstArg->value->setAttribute('isMockTarget', true);
        }
    }
}
