<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

use Closure;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use Throwable;

use function array_values;
use function class_exists;
use function file_get_contents;
use function is_file;
use function sprintf;

final readonly class TestUsageScanner
{
    private ConsumedUsageExtractor $consumedUsageExtractor;

    private ScanResultCache $scanResultCache;

    private Parser $parser;

    /** @var Closure(string): ScanResult */
    private Closure $scanFactory;

    /**
     * @param null|Closure(string): (string|false) $readFile
     */
    public function __construct(
        ?ConsumedUsageExtractor $consumedUsageExtractor = null,
        ?ScanResultCache $scanResultCache = null,
        private ?Closure $readFile = null,
    ) {
        $this->consumedUsageExtractor = $consumedUsageExtractor ?? new ConsumedUsageExtractor();
        $this->scanResultCache        = $scanResultCache ?? new ScanResultCache();
        $this->parser                 = (new ParserFactory())->createForNewestSupportedVersion();
        $this->scanFactory            = fn (string $className): ScanResult => $this->scanUncached($className);
    }

    public function scan(string $testClassName): ScanResult
    {
        return $this->scanResultCache->get($testClassName, $this->scanFactory);
    }

    private function scanUncached(string $testClassName): ScanResult
    {
        if (! class_exists($testClassName)) {
            return ScanResult::uninspectable(sprintf('Test class "%s" could not be reflected.', $testClassName));
        }

        $reflectionClass = new ReflectionClass($testClassName);

        $fileName = $reflectionClass->getFileName();

        if ($fileName === false || ! is_file($fileName)) {
            return ScanResult::uninspectable(sprintf('Source file for "%s" could not be found.', $testClassName));
        }

        $source = $this->readFile instanceof Closure
            ? ($this->readFile)($fileName)
            : file_get_contents($fileName);

        if ($source === false) {
            return ScanResult::uninspectable(sprintf('Source file "%s" could not be read.', $fileName));
        }

        try {
            $nodes = $this->parser->parse($source);
        } catch (Throwable $throwable) {
            return ScanResult::uninspectable(
                sprintf(
                    'Source file "%s" could not be parsed: %s',
                    $fileName,
                    $throwable->getMessage()
                )
            );
        }

        // @codeCoverageIgnoreStart
        if ($nodes === null) {
            return ScanResult::uninspectable(sprintf('Source file "%s" could not be parsed.', $fileName));
        }

        // @codeCoverageIgnoreEnd

        return ScanResult::inspectable($this->consumedUsageExtractor->extract(array_values($nodes)));
    }
}
