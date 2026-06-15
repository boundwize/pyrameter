<?php

declare(strict_types=1);

namespace Pyrameter\Detection;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;

final class ConsumedClassExtractor
{
    /**
     * @param list<Node> $nodes
     * @return list<string>
     */
    public function extract(array $nodes): array
    {
        $traverser = new NodeTraverser();
        $collector = new ConsumedClassVisitor();

        $traverser->addVisitor(new ParentConnectingVisitor());
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector);
        $traverser->traverse($nodes);

        return $collector->consumedClasses();
    }
}
