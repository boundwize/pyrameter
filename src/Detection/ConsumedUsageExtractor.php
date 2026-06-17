<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

final class ConsumedUsageExtractor
{
    /**
     * @param list<Node> $nodes
     * @return list<string>
     */
    public function extract(array $nodes): array
    {
        $nodeTraverser        = new NodeTraverser();
        $consumedUsageVisitor = new ConsumedUsageVisitor();

        $nodeTraverser->addVisitor(new NameResolver());
        $nodeTraverser->addVisitor($consumedUsageVisitor);
        $nodeTraverser->traverse($nodes);

        return $consumedUsageVisitor->consumedUsages();
    }
}
