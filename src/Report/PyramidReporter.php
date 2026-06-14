<?php

declare(strict_types=1);

namespace Pyrameter\Report;

use Pyrameter\PyramidSummary;
use Pyrameter\Target\TargetEvaluation;
use Pyrameter\TestKind;

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
            sprintf('Shape: %s %s', $shape->name, $shape->symbol()),
            '',
            'Kind          Tests   Actual   Target      Status',
        ];

        foreach (TestKind::ordered() as $kind) {
            $status = $targets->status($kind);

            $lines[] = sprintf(
                '%-12s %6d %8s   %-9s   %s',
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
