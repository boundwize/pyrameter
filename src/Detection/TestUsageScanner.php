<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Detection;

use Closure;
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
    private ConsumedClassExtractor $consumedClassExtractor;

    private ScanResultCache $scanResultCache;

    /**
     * @param null|Closure(string): (string|false) $readFile
     */
    public function __construct(
        ?ConsumedClassExtractor $consumedClassExtractor = null,
        ?ScanResultCache $scanResultCache = null,
        private ?Closure $readFile = null,
    ) {
        $this->consumedClassExtractor = $consumedClassExtractor ?? new ConsumedClassExtractor();
        $this->scanResultCache        = $scanResultCache ?? new ScanResultCache();
    }

    public function scan(string $testClassName): ScanResult
    {
        return $this->scanResultCache->get(
            $testClassName,
            fn (string $className): ScanResult => $this->scanUncached($className)
        );
    }

    private function scanUncached(string $testClassName): ScanResult
    {
        if (! class_exists($testClassName)) {
            return ScanResult::unknown(sprintf('Test class "%s" could not be reflected.', $testClassName));
        }

        $reflectionClass = new ReflectionClass($testClassName);

        $fileName = $reflectionClass->getFileName();

        if ($fileName === false || ! is_file($fileName)) {
            return ScanResult::unknown(sprintf('Source file for "%s" could not be found.', $testClassName));
        }

        $source = $this->readFile instanceof Closure
            ? ($this->readFile)($fileName)
            : file_get_contents($fileName);

        if ($source === false) {
            return ScanResult::unknown(sprintf('Source file "%s" could not be read.', $fileName));
        }

        try {
            $parser = (new ParserFactory())->createForHostVersion();
            $nodes  = $parser->parse($source);
        } catch (Throwable $throwable) {
            return ScanResult::unknown(
                sprintf(
                    'Source file "%s" could not be parsed: %s',
                    $fileName,
                    $throwable->getMessage()
                )
            );
        }

        // @codeCoverageIgnoreStart
        if ($nodes === null) {
            return ScanResult::unknown(sprintf('Source file "%s" could not be parsed.', $fileName));
        }

        // @codeCoverageIgnoreEnd

        return ScanResult::inspectable($this->consumedClassExtractor->extract(array_values($nodes)));
    }
}
