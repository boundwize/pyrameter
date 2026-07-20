<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Detection;

use Boundwize\Pyrameter\Detection\ConsumedUsageExtractor;
use Boundwize\Pyrameter\Detection\ConsumedUsageVisitor;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

use function array_values;
use function sort;

final class ConsumedUsageExtractorTest extends TestCase
{
    public function testItExtractsClassesFromLanguageConstructsAndTypes(): void
    {
        $usages = $this->extract(<<<'PHP'
<?php

namespace Boundwize\Pyrameter\Tests\Fixtures\Extractor;

use Vendor\ImportedThing;
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
        new ImportedThing();
        new GroupedClass();
        new RenamedGroupedClass();
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

        sort($usages);

        $this->assertSame([
            'class:Vendor\Attributes\ExampleAttribute',
            'class:Vendor\BaseClass',
            'class:Vendor\Constructed\Thing',
            'class:Vendor\Contracts\FirstContract',
            'class:Vendor\Contracts\ParentA',
            'class:Vendor\Contracts\ParentB',
            'class:Vendor\Contracts\SecondContract',
            'class:Vendor\Grouped\AnotherGroupedClass',
            'class:Vendor\Grouped\GroupedClass',
            'class:Vendor\ImportedThing',
            'class:Vendor\Params\Input',
            'class:Vendor\Returns\Output',
            'class:Vendor\StaticCall\Thing',
            'class:Vendor\StaticProperty\Thing',
            'class:Vendor\Traits\FirstTrait',
            'class:Vendor\Traits\SecondTrait',
            'class:Vendor\Types\IntersectionA',
            'class:Vendor\Types\IntersectionB',
            'class:Vendor\Types\NullableType',
            'class:Vendor\Types\UnionA',
            'class:Vendor\Types\UnionB',
        ], $usages);
    }

    public function testItIgnoresNonClassImportsWithinAMixedGroupUse(): void
    {
        $usages = $this->extract(<<<'PHP'
<?php

use Vendor\MixedGroup\{GroupedClass, function helperInGroup};

new GroupedClass();
PHP);

        $this->assertSame(['class:Vendor\MixedGroup\GroupedClass'], $usages);
    }

    public function testItIgnoresUnusedImports(): void
    {
        $usages = $this->extract(<<<'PHP'
<?php

use Vendor\Unused\Thing;
use Vendor\Used\Thing as UsedThing;

new UsedThing();
PHP);

        $this->assertSame(['class:Vendor\Used\Thing'], $usages);
    }

    public function testItExtractsClassesFromInstanceofAndCatchTypes(): void
    {
        $usages = $this->extract(<<<'PHP'
<?php

namespace Boundwize\Pyrameter\Tests\Fixtures\Extractor;

use Vendor\Checks\ImportedCheck;
use Vendor\Exceptions\AliasedException;

final class ControlFlowConsumer
{
    public function method(object $value, string $className): void
    {
        $value instanceof \Vendor\Checks\DirectCheck;
        $value instanceof ImportedCheck;
        $value instanceof self;
        $value instanceof $className;

        try {
        } catch (\Vendor\Exceptions\FirstException|AliasedException $exception) {
        } catch (\Vendor\Exceptions\SecondException $exception) {
        }
    }
}
PHP);

        sort($usages);

        $this->assertSame([
            'class:Vendor\Checks\DirectCheck',
            'class:Vendor\Checks\ImportedCheck',
            'class:Vendor\Exceptions\AliasedException',
            'class:Vendor\Exceptions\FirstException',
            'class:Vendor\Exceptions\SecondException',
        ], $usages);
    }

    public function testItHandlesClassConstantsThatAreNotMockTargets(): void
    {
        $usages = $this->extract(<<<'PHP'
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

        sort($usages);

        $this->assertSame([
            'class:Vendor\ClassConstant\Direct',
            'class:Vendor\ClassConstant\DynamicMethod',
            'class:Vendor\ClassConstant\FunctionArgument',
            'class:Vendor\ClassConstant\NotFirstArgument',
            'function:function_call',
        ], $usages);
    }

    public function testItIgnoresTypeDeclarationsOfMockedClasses(): void
    {
        $usages = $this->extract(<<<'PHP_WRAP'
        <?php
        
        use PHPUnit\Framework\MockObject\MockObject;
        
        final class Example
        {
            private \Vendor\Mocked\TypedIntersection&MockObject $intersection;
            private \Vendor\Mocked\TypedOnly $typedOnly;
        
            protected function setUp(): void
            {
                $this->intersection = $this->createMock(\Vendor\Mocked\TypedIntersection::class);
                $this->typedOnly    = $this->createMock(\Vendor\Mocked\TypedOnly::class);
            }
        }
        PHP_WRAP);

        $this->assertSame(['class:PHPUnit\Framework\MockObject\MockObject'], $usages);
    }

    public function testItIgnoresMockResultIntersectionTypesWithoutAMockCreationCall(): void
    {
        $usages = $this->extract(<<<'PHP_WRAP'
        <?php
        
        use PHPUnit\Framework\MockObject\MockObject;
        use PHPUnit\Framework\MockObject\Stub;
        
        final class Example
        {
            private \Vendor\Mocked\MockedDependency&MockObject $mock;
            private \Vendor\Mocked\StubbedDependency&Stub $stub;
        }
        PHP_WRAP);

        sort($usages);

        $this->assertSame([
            'class:PHPUnit\Framework\MockObject\MockObject',
            'class:PHPUnit\Framework\MockObject\Stub',
        ], $usages);
    }

    public function testItKeepsMockedClassesUsedOutsideTypeDeclarations(): void
    {
        $usages = $this->extract(<<<'PHP'
<?php

final class Example
{
    private \Vendor\Mocked\RealDependency $dependency;

    public function method(): void
    {
        $this->createMock(\Vendor\Mocked\RealDependency::class);
        new \Vendor\Mocked\RealDependency();
    }
}
PHP);

        $this->assertSame(['class:Vendor\Mocked\RealDependency'], $usages);
    }

    public function testItExtractsFunctionCalls(): void
    {
        $usages = $this->extract(<<<'PHP'
<?php

namespace Boundwize\Pyrameter\Tests\Fixtures\Extractor;

use function Vendor\Filesystem\ReadFixture as read_fixture;

final class FunctionConsumer
{
    public function method(): void
    {
        File_Get_Contents(__FILE__);
        \file_put_contents(__FILE__, '');
        read_fixture(__FILE__);

        $dynamic = 'file_exists';
        $dynamic(__FILE__);
    }
}
PHP);

        sort($usages);

        $this->assertSame([
            'function:file_get_contents',
            'function:file_put_contents',
            'function:vendor\filesystem\readfixture',
        ], $usages);
    }

    public function testItResetsConsumedUsagesBetweenExtractions(): void
    {
        $parser                 = (new ParserFactory())->createForNewestSupportedVersion();
        $consumedUsageExtractor = new ConsumedUsageExtractor();

        $firstNodes  = $parser->parse(<<<'PHP'
<?php

new Vendor\FirstUsage();
PHP);
        $secondNodes = $parser->parse(<<<'PHP'
<?php

new Vendor\SecondUsage();
PHP);

        $this->assertIsArray($firstNodes);
        $this->assertIsArray($secondNodes);
        $this->assertSame(['class:Vendor\FirstUsage'], $consumedUsageExtractor->extract(array_values($firstNodes)));
        $this->assertSame(['class:Vendor\SecondUsage'], $consumedUsageExtractor->extract(array_values($secondNodes)));
    }

    public function testItIgnoresEmptyFunctionNames(): void
    {
        $consumedUsageVisitor = new ConsumedUsageVisitor();
        $consumedUsageVisitor->enterNode(new FuncCall(new Name([''])));

        $this->assertSame([], $consumedUsageVisitor->consumedUsages());
    }

    /**
     * @return list<string>
     */
    private function extract(string $source): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $nodes  = $parser->parse($source);

        $this->assertIsArray($nodes);

        return (new ConsumedUsageExtractor())->extract(array_values($nodes));
    }
}
