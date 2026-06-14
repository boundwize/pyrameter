<?php

declare(strict_types=1);

namespace Pyrameter\Detection;

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
    private ConsumedClassExtractor $extractor;

    private ScanResultCache $cache;

    public function __construct(
        ?ConsumedClassExtractor $extractor = null,
        ?ScanResultCache $cache = null,
    ) {
        $this->extractor = $extractor ?? new ConsumedClassExtractor();
        $this->cache     = $cache ?? new ScanResultCache();
    }

    public function scan(string $testClassName): ScanResult
    {
        return $this->cache->get($testClassName, fn (string $className): ScanResult => $this->scanUncached($className));
    }

    private function scanUncached(string $testClassName): ScanResult
    {
        if (! class_exists($testClassName)) {
            return ScanResult::unknown(sprintf('Test class "%s" could not be reflected.', $testClassName));
        }

        $reflection = new ReflectionClass($testClassName);

        $fileName = $reflection->getFileName();

        if ($fileName === false || ! is_file($fileName)) {
            return ScanResult::unknown(sprintf('Source file for "%s" could not be found.', $testClassName));
        }

        $source = file_get_contents($fileName);

        if ($source === false) {
            return ScanResult::unknown(sprintf('Source file "%s" could not be read.', $fileName));
        }

        try {
            $parser = (new ParserFactory())->createForHostVersion();
            $nodes  = $parser->parse($source);
        } catch (Throwable $exception) {
            return ScanResult::unknown(sprintf('Source file "%s" could not be parsed: %s', $fileName, $exception->getMessage()));
        }

        // @codeCoverageIgnoreStart
        if ($nodes === null) {
            return ScanResult::unknown(sprintf('Source file "%s" could not be parsed.', $fileName));
        }
        // @codeCoverageIgnoreEnd

        return ScanResult::inspectable($this->extractor->extract(array_values($nodes)));
    }
}
