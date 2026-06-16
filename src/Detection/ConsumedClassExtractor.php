<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

final class ConsumedClassExtractor
{
    /**
     * @param list<Node> $nodes
     * @return list<string>
     */
    public function extract(array $nodes): array
    {
        $nodeTraverser        = new NodeTraverser();
        $consumedClassVisitor = new ConsumedClassVisitor();

        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($consumedClassVisitor);
        $nodeTraverser->traverse($nodes);

        return $consumedClassVisitor->consumedClasses();
    }
}
