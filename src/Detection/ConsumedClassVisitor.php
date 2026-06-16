<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ClassConstFetch;
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
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UnionType;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

use function in_array;
use function ltrim;
use function strtolower;

final class ConsumedClassVisitor extends NodeVisitorAbstract
{
    /** @var array<string, array{import: bool, normal: bool, mock: bool}> */
    private array $references = [];

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
        if ($node instanceof UseItem && $this->isClassUse($node)) {
            $this->add($this->useName($node), 'import');
        }

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

        if ($node instanceof ClassConstFetch && $node->class instanceof Name && $this->isClassConstant($node)) {
            $this->addName($node->class, $this->isMockTarget($node) ? 'mock' : 'normal');
        }

        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $this->addName($node->class);
        }

        if ($node instanceof StaticPropertyFetch && $node->class instanceof Name) {
            $this->addName($node->class);
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
    public function consumedClasses(): array
    {
        $classes = [];

        foreach ($this->references as $className => $referenceKinds) {
            if ($referenceKinds['normal']) {
                $classes[] = $className;
                continue;
            }

            if ($referenceKinds['import'] && ! $referenceKinds['mock']) {
                $classes[] = $className;
            }
        }

        return $classes;
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

    /**
     * @param 'import'|'normal'|'mock' $referenceKind
     */
    private function addName(?Name $name, string $referenceKind = 'normal'): void
    {
        if (! $name instanceof Name) {
            return;
        }

        $resolvedName = $name->getAttribute('resolvedName');
        $className    = $resolvedName instanceof Name ? $resolvedName->toString() : $name->toString();

        $this->add($className, $referenceKind);
    }

    /**
     * @param 'import'|'normal'|'mock' $referenceKind
     */
    private function add(string $className, string $referenceKind): void
    {
        $className = ltrim($className, '\\');

        if ($className === '' || in_array(strtolower($className), ['self', 'static', 'parent'], true)) {
            return;
        }

        $reference = $this->references[$className] ?? [
            'import' => false,
            'normal' => false,
            'mock'   => false,
        ];

        if ($referenceKind === 'import') {
            $reference['import'] = true;
        } elseif ($referenceKind === 'normal') {
            $reference['normal'] = true;
        } else {
            $reference['mock'] = true;
        }

        $this->references[$className] = $reference;
    }

    private function isClassConstant(ClassConstFetch $classConstFetch): bool
    {
        return $classConstFetch->name instanceof Identifier
            && strtolower($classConstFetch->name->toString()) === 'class';
    }

    private function isMockTarget(ClassConstFetch $classConstFetch): bool
    {
        $parent = $classConstFetch->getAttribute('parent');

        if (! $parent instanceof Arg || $parent->value !== $classConstFetch) {
            return false;
        }

        $call = $parent->getAttribute('parent');

        if (! $call instanceof MethodCall && ! $call instanceof StaticCall) {
            return false;
        }

        if (! $call->name instanceof Identifier) {
            return false;
        }

        if (($call->args[0] ?? null) !== $parent) {
            return false;
        }

        return in_array($call->name->toString(), $this->mockMethods, true);
    }

    private function isClassUse(UseItem $useItem): bool
    {
        $parent = $useItem->getAttribute('parent');

        if ($parent instanceof Use_) {
            return $parent->type === Use_::TYPE_NORMAL;
        }

        if ($parent instanceof GroupUse) {
            if ($parent->type !== Use_::TYPE_UNKNOWN && $parent->type !== Use_::TYPE_NORMAL) {
                return false;
            }

            return $useItem->type === Use_::TYPE_UNKNOWN || $useItem->type === Use_::TYPE_NORMAL;
        }

        return true;
    }

    private function useName(UseItem $useItem): string
    {
        $parent = $useItem->getAttribute('parent');

        if ($parent instanceof GroupUse) {
            return $parent->prefix->toString() . '\\' . $useItem->name->toString();
        }

        return $useItem->name->toString();
    }
}
