<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Report;

use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Target\TargetEvaluation;
use Boundwize\Pyrameter\TestKind;

use function fwrite;
use function implode;
use function sprintf;

use const PHP_EOL;
use const STDOUT;

final readonly class PyramidReporter
{
    public function print(PyramidSummary $summary, TargetEvaluation $targets, SuiteShape $shape): void
    {
        fwrite(STDOUT, PHP_EOL . $this->render($summary, $targets, $shape) . PHP_EOL);
    }

    public function render(PyramidSummary $summary, TargetEvaluation $targets, SuiteShape $shape): string
    {
        $lines = [
            'Pyrameter',
            '=========',
            '',
            sprintf('Shape: %s', $shape->name),
            sprintf('Result: %s', $targets->allPassed() ? 'Passed ✓' : 'Violated ⚠'),
            '',
            'Kind          Tests   Actual   Target      Status',
        ];

        foreach (TestKind::ordered() as $kind) {
            $status = $targets->status($kind);

            $lines[] = sprintf(
                '%-12s %6d %8s   %-10s   %s',
                $kind->label(),
                $summary->count($kind),
                sprintf('%4.1f%%', $summary->percentage($kind)),
                $status->label(),
                $status->symbol(),
            );
        }

        $lines[] = '';
        $lines[] = sprintf('Total: %d tests', $summary->total);
        $lines[] = '';
        $lines[] = $shape->verdict;

        return implode(PHP_EOL, $lines);
    }
}
