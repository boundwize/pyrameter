<?php

declare(strict_types=1);

namespace Pyrameter\Tests\Detection;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Pyrameter\Detection\ConsumedClassExtractor;

use function array_values;
use function sort;

final class ConsumedClassExtractorTest extends TestCase
{
    public function testItExtractsClassesFromLanguageConstructsAndTypes(): void
    {
        $classes = $this->extract(<<<'PHP'
<?php

namespace Pyrameter\Tests\Fixtures\Extractor;

use Vendor\ImportedOnly;
use Vendor\Grouped\{GroupedClass, AnotherGroupedClass as RenamedGroupedClass};
use function Vendor\Functions\{helper};
use const Vendor\Constants\{VALUE};

#[\Vendor\Attributes\ExampleAttribute]
final class Child extends \Vendor\BaseClass implements \Vendor\Contracts\FirstContract, \Vendor\Contracts\SecondContract
{
    use \Vendor\Traits\FirstTrait, \Vendor\Traits\SecondTrait;

    private ?\Vendor\Types\NullableType $nullable;
    private \Vendor\Types\UnionA|\Vendor\Types\UnionB $union;
    private \Vendor\Types\IntersectionA&\Vendor\Types\IntersectionB $intersection;

    public function method(\Vendor\Params\Input $input): \Vendor\Returns\Output
    {
        new \Vendor\Constructed\Thing();
        \Vendor\StaticCall\Thing::make();
        \Vendor\StaticProperty\Thing::$store;

        return new \Vendor\Returns\Output();
    }
}

final class EmptyClass
{
}

interface ChildInterface extends \Vendor\Contracts\ParentA, \Vendor\Contracts\ParentB
{
}
PHP);

        sort($classes);

        self::assertSame([
            'Vendor\Attributes\ExampleAttribute',
            'Vendor\BaseClass',
            'Vendor\Constructed\Thing',
            'Vendor\Contracts\FirstContract',
            'Vendor\Contracts\ParentA',
            'Vendor\Contracts\ParentB',
            'Vendor\Contracts\SecondContract',
            'Vendor\Grouped\AnotherGroupedClass',
            'Vendor\Grouped\GroupedClass',
            'Vendor\ImportedOnly',
            'Vendor\Params\Input',
            'Vendor\Returns\Output',
            'Vendor\StaticCall\Thing',
            'Vendor\StaticProperty\Thing',
            'Vendor\Traits\FirstTrait',
            'Vendor\Traits\SecondTrait',
            'Vendor\Types\IntersectionA',
            'Vendor\Types\IntersectionB',
            'Vendor\Types\NullableType',
            'Vendor\Types\UnionA',
            'Vendor\Types\UnionB',
        ], $classes);
    }

    public function testItHandlesClassConstantsThatAreNotMockTargets(): void
    {
        $classes = $this->extract(<<<'PHP'
<?php

final class Example
{
    public function method(string $method): void
    {
        $direct = \Vendor\ClassConstant\Direct::class;
        function_call(\Vendor\ClassConstant\FunctionArgument::class);
        $this->{$method}(\Vendor\ClassConstant\DynamicMethod::class);
        $this->notMock('prefix', \Vendor\ClassConstant\NotFirstArgument::class);
        self::createStub(\Vendor\ClassConstant\StaticMockTarget::class);
    }
}
PHP);

        sort($classes);

        self::assertSame([
            'Vendor\ClassConstant\Direct',
            'Vendor\ClassConstant\DynamicMethod',
            'Vendor\ClassConstant\FunctionArgument',
            'Vendor\ClassConstant\NotFirstArgument',
        ], $classes);
    }

    public function testItAcceptsStandaloneUseNodesWithoutParentNodes(): void
    {
        $classes = (new ConsumedClassExtractor())->extract([
            new UseUse(new Name('Vendor\LooseImport')),
        ]);

        self::assertSame(['Vendor\LooseImport'], $classes);
    }

    /**
     * @return list<string>
     */
    private function extract(string $source): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $nodes  = $parser->parse($source);

        self::assertIsArray($nodes);

        return (new ConsumedClassExtractor())->extract(array_values($nodes));
    }
}
