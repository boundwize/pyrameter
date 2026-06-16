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
    public function print(
        PyramidSummary $pyramidSummary,
        TargetEvaluation $targetEvaluation,
        SuiteShape $suiteShape
    ): void {
        fwrite(
            STDOUT,
            PHP_EOL . $this->render($pyramidSummary, $targetEvaluation, $suiteShape) . PHP_EOL
        );
    }

    public function render(
        PyramidSummary $pyramidSummary,
        TargetEvaluation $targetEvaluation,
        SuiteShape $suiteShape
    ): string {
        $lines = [
            'Pyrameter',
            '=========',
            '',
            sprintf('Shape: %s', $suiteShape->name),
            sprintf('Result: %s', $targetEvaluation->allPassed()
                ? 'Passed ✓' : 'Violated ⚠'),
            '',
            'Kind          Tests   Actual   Target      Status',
        ];

        foreach (TestKind::ordered() as $testKind) {
            $status = $targetEvaluation->status($testKind);

            $lines[] = sprintf(
                '%-12s %6d %8s   %-10s   %s',
                $testKind->label(),
                $pyramidSummary->count($testKind),
                sprintf(
                    '%4.1f%%',
                    $pyramidSummary->percentage($testKind)
                ),
                $status->label(),
                $status->symbol(),
            );
        }

        $lines[] = '';
        $lines[] = sprintf('Total: %d tests', $pyramidSummary->total);
        $lines[] = '';
        $lines[] = $suiteShape->verdict;

        return implode(PHP_EOL, $lines);
    }
}
