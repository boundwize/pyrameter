<?php

declare(strict_types=1);

namespace Pyrameter\Detection;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UnionType;
use PhpParser\Node\UseItem;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\NodeVisitorAbstract;

use function in_array;
use function ltrim;
use function strtolower;

final class ConsumedClassExtractor
{
    /**
     * @param list<Node> $nodes
     * @return list<string>
     */
    public function extract(array $nodes): array
    {
        $traverser = new NodeTraverser();
        $collector = new class extends NodeVisitorAbstract {
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

                if ($node instanceof Node\Param) {
                    $this->addType($node->type);
                }

                if ($node instanceof Node\Stmt\Property) {
                    $this->addType($node->type);
                }

                if ($node instanceof Node\FunctionLike) {
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
                if ($name === null) {
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

            private function isClassConstant(ClassConstFetch $node): bool
            {
                return $node->name instanceof Identifier
                    && strtolower($node->name->toString()) === 'class';
            }

            private function isMockTarget(ClassConstFetch $node): bool
            {
                $parent = $node->getAttribute('parent');

                if (! $parent instanceof Arg || $parent->value !== $node) {
                    return false;
                }

                $call = $parent->getAttribute('parent');

                if (! $call instanceof Node\Expr\MethodCall && ! $call instanceof StaticCall) {
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

            private function isClassUse(UseItem $node): bool
            {
                $parent = $node->getAttribute('parent');

                if ($parent instanceof Use_) {
                    return $parent->type === Use_::TYPE_NORMAL;
                }

                if ($parent instanceof GroupUse) {
                    if ($parent->type !== Use_::TYPE_UNKNOWN && $parent->type !== Use_::TYPE_NORMAL) {
                        return false;
                    }

                    return $node->type === Use_::TYPE_UNKNOWN || $node->type === Use_::TYPE_NORMAL;
                }

                return true;
            }

            private function useName(UseUse $node): string
            {
                $parent = $node->getAttribute('parent');

                if ($parent instanceof GroupUse) {
                    return $parent->prefix->toString() . '\\' . $node->name->toString();
                }

                return $node->name->toString();
            }
        };

        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector);
        $traverser->traverse($nodes);

        return $collector->consumedClasses();
    }
}
