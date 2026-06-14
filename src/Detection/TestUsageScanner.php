<?php

declare(strict_types=1);

namespace Pyrameter\Detection;

use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use Throwable;

final class TestUsageScanner
{
    private ConsumedClassExtractor $extractor;

    private ScanResultCache $cache;

    public function __construct(
        ?ConsumedClassExtractor $extractor = null,
        ?ScanResultCache $cache = null,
    ) {
        $this->extractor = $extractor ?? new ConsumedClassExtractor();
        $this->cache = $cache ?? new ScanResultCache();
    }

    /**
     * @param class-string $testClassName
     */
    public function scan(string $testClassName): ScanResult
    {
        return $this->cache->get($testClassName, fn (string $className): ScanResult => $this->scanUncached($className));
    }

    /**
     * @param class-string $testClassName
     */
    private function scanUncached(string $testClassName): ScanResult
    {
        try {
            $reflection = new ReflectionClass($testClassName);
        } catch (ReflectionException $exception) {
            return ScanResult::unknown($exception->getMessage());
        }

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
            $nodes = $parser->parse($source);
        } catch (Throwable $exception) {
            return ScanResult::unknown(sprintf('Source file "%s" could not be parsed: %s', $fileName, $exception->getMessage()));
        }

        if ($nodes === null) {
            return ScanResult::unknown(sprintf('Source file "%s" could not be parsed.', $fileName));
        }

        return ScanResult::inspectable($this->extractor->extract($nodes));
    }
}
