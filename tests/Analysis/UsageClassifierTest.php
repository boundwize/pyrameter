<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Tests\Analysis;

use Boundwize\Pyrameter\Analysis\UsageClassifier;
use Boundwize\Pyrameter\Config\PyrameterConfig;
use Boundwize\Pyrameter\Rule\UsageRule;
use Boundwize\Pyrameter\Rule\UsageType;
use Boundwize\Pyrameter\TestKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UsageClassifierTest extends TestCase
{
    /**
     * @param non-empty-string $className
     */
    #[DataProvider('phpRedisGlobalClassCases')]
    public function testDefaultRulesClassifyPhpRedisGlobalClassesAsIntegration(string $className): void
    {
        $pyrameterConfig = PyrameterConfig::defaults();
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::Integration, $usageClassifier->classify([$className]));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function phpRedisGlobalClassCases(): iterable
    {
        yield 'Redis client' => ['Redis'];
        yield 'Redis cluster client' => ['RedisCluster'];
        yield 'Redis sentinel client' => ['RedisSentinel'];
    }

    /**
     * @param non-empty-string $functionName
     */
    #[DataProvider('fileOperationFunctionCases')]
    public function testDefaultRulesClassifyFileOperationFunctionsAsIntegration(string $functionName): void
    {
        $pyrameterConfig = PyrameterConfig::defaults();
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['function:' . $functionName]));
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function fileOperationFunctionCases(): iterable
    {
        yield 'file_get_contents' => ['file_get_contents'];
        yield 'file_put_contents' => ['file_put_contents'];
        yield 'fopen' => ['fopen'];
        yield 'fread' => ['fread'];
        yield 'fwrite' => ['fwrite'];
        yield 'fgets' => ['fgets'];
        yield 'fgetc' => ['fgetc'];
        yield 'fclose' => ['fclose'];
        yield 'feof' => ['feof'];
        yield 'rewind' => ['rewind'];
        yield 'fseek' => ['fseek'];
        yield 'ftell' => ['ftell'];
        yield 'fflush' => ['fflush'];
        yield 'ftruncate' => ['ftruncate'];
        yield 'file_exists' => ['file_exists'];
        yield 'is_file' => ['is_file'];
        yield 'is_dir' => ['is_dir'];
        yield 'is_readable' => ['is_readable'];
        yield 'is_writable' => ['is_writable'];
        yield 'is_executable' => ['is_executable'];
        yield 'filesize' => ['filesize'];
        yield 'filemtime' => ['filemtime'];
        yield 'filectime' => ['filectime'];
        yield 'fileatime' => ['fileatime'];
        yield 'fileperms' => ['fileperms'];
        yield 'fileowner' => ['fileowner'];
        yield 'filegroup' => ['filegroup'];
        yield 'filetype' => ['filetype'];
        yield 'stat' => ['stat'];
        yield 'lstat' => ['lstat'];
        yield 'touch' => ['touch'];
        yield 'copy' => ['copy'];
        yield 'rename' => ['rename'];
        yield 'unlink' => ['unlink'];
        yield 'mkdir' => ['mkdir'];
        yield 'rmdir' => ['rmdir'];
        yield 'opendir' => ['opendir'];
        yield 'readdir' => ['readdir'];
        yield 'closedir' => ['closedir'];
        yield 'rewinddir' => ['rewinddir'];
        yield 'scandir' => ['scandir'];
        yield 'glob' => ['glob'];
        yield 'chdir' => ['chdir'];
        yield 'getcwd' => ['getcwd'];
        yield 'realpath' => ['realpath'];
        yield 'chmod' => ['chmod'];
        yield 'chown' => ['chown'];
        yield 'chgrp' => ['chgrp'];
        yield 'umask' => ['umask'];
        yield 'link' => ['link'];
        yield 'symlink' => ['symlink'];
        yield 'readlink' => ['readlink'];
        yield 'is_link' => ['is_link'];
        yield 'tmpfile' => ['tmpfile'];
        yield 'tempnam' => ['tempnam'];
        yield 'flock' => ['flock'];
        yield 'is_uploaded_file' => ['is_uploaded_file'];
        yield 'move_uploaded_file' => ['move_uploaded_file'];
        yield 'fgetcsv' => ['fgetcsv'];
        yield 'fputcsv' => ['fputcsv'];
        yield 'parse_ini_file' => ['parse_ini_file'];
    }

    public function testNamespaceRulesMatchOnlyConfiguredPrefixCaseInsensitively(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Tests\Browser\\', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['App\Tests\Browser\CheckoutTest']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['class:App\Tests\Browser\\']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['App\Tests\Browsering\CheckoutTest']));
        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['app\Tests\Browser\CheckoutTest']));
    }

    public function testExactRulesDoNotMatchNamespacePrefixes(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Tests\Browser', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['App\Tests\Browser']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['App\Tests\Browser\CheckoutTest']));
    }

    public function testNamespaceRulesNormalizeConfiguredAndConsumedUsage(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Tests\Browser\\', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['\\APP\Tests\Browser\CheckoutTest']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['\\APP\Tests\Browsering\CheckoutTest']));
    }

    public function testNamespaceRulesRespectNamespaceSeparators(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\\', TestKind::Functional),
        ]);

        $this->assertSame(TestKind::Functional, $usageClassifier->classify(['App\Service']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['Application\Service']));
    }

    public function testExactRulesNormalizeConfiguredAndConsumedUsageButDoNotMatchPrefixes(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('file_get_contents', TestKind::Integration, UsageType::Function),
        ]);

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['function:FILE_GET_CONTENTS']));
        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['function:FILE_GET_CONTENTS_EXTRA']));
    }

    public function testFunctionRulesDoNotMatchClassLikeUsages(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('file_get_contents', TestKind::Integration, UsageType::Function),
        ]);

        $this->assertSame(TestKind::Unit, $usageClassifier->classify(['class:file_get_contents']));
    }

    public function testUnknownTypedConsumedUsageFallsBackToClassLikeMatching(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('custom:file_get_contents', TestKind::Integration),
        ]);

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['custom:FILE_GET_CONTENTS']));
    }

    public function testExactRulesCanShortCircuitOnE2EAfterNormalization(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('run_browser_session', TestKind::E2E, UsageType::Function),
            new UsageRule('file_get_contents', TestKind::Integration, UsageType::Function),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['function:RUN_BROWSER_SESSION']));
    }

    public function testDuplicateExactRulesUseTheHeaviestKind(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\Service', TestKind::Functional),
            new UsageRule('App\Service', TestKind::Integration),
        ]);

        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['App\Service']));
    }

    public function testRuleCanBeSuppressedByAnotherConsumedUsage(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('Framework\DatabaseTrait', TestKind::Integration, unless: ['Framework\ControllerTrait']),
            new UsageRule('Framework\ControllerTrait', TestKind::Functional),
        ]);

        $this->assertSame(
            TestKind::Functional,
            $usageClassifier->classify(['Framework\ControllerTrait', 'Framework\DatabaseTrait']),
        );
        $this->assertSame(
            TestKind::Functional,
            $usageClassifier->classify(['Framework\DatabaseTrait', 'Framework\ControllerTrait']),
        );
        $this->assertSame(TestKind::Integration, $usageClassifier->classify(['Framework\DatabaseTrait']));
    }

    public function testFunctionRuleCanBeSuppressedByClassLikeUsage(): void
    {
        $pyrameterConfig = PyrameterConfig::create()
            ->usesFunction(
                'file_put_contents',
                TestKind::Integration,
                unless: [self::class],
            )
            ->usesClass(self::class, TestKind::Functional);
        $usageClassifier = new UsageClassifier($pyrameterConfig->usageRules());

        $this->assertSame(
            TestKind::Functional,
            $usageClassifier->classify([
                'function:file_put_contents',
                'class:' . self::class,
            ]),
        );
    }

    public function testHeaviestMatchingRuleWinsAfterRulesArePrecompiled(): void
    {
        $usageClassifier = new UsageClassifier([
            new UsageRule('App\\', TestKind::Functional),
            new UsageRule('App\Tests\\', TestKind::Integration),
            new UsageRule('App\Tests\Browser\\', TestKind::E2E),
        ]);

        $this->assertSame(TestKind::E2E, $usageClassifier->classify(['App\Tests\Browser\CheckoutTest']));
    }
}
