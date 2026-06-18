<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

final readonly class ConsumedUsageExtractor
{
    private NodeTraverser $nodeTraverser;

    private ConsumedUsageVisitor $consumedUsageVisitor;

    public function __construct()
    {
        $this->nodeTraverser        = new NodeTraverser();
        $this->consumedUsageVisitor = new ConsumedUsageVisitor();

        $this->nodeTraverser->addVisitor(new NameResolver());
        $this->nodeTraverser->addVisitor($this->consumedUsageVisitor);
    }

    /**
     * @param list<Node> $nodes
     * @return list<string>
     */
    public function extract(array $nodes): array
    {
        $this->consumedUsageVisitor->reset();
        $this->nodeTraverser->traverse($nodes);

        return $this->consumedUsageVisitor->consumedUsages();
    }
}
