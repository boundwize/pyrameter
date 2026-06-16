<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Report;

use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Target\TargetEvaluation;
use Boundwize\Pyrameter\TestKind;

use function array_fill;
use function implode;
use function intdiv;
use function max;
use function preg_match_all;
use function sprintf;
use function strlen;

use const PHP_EOL;

final readonly class PyramidReporter
{
    public function print(
        PyramidSummary $pyramidSummary,
        TargetEvaluation $targetEvaluation,
        SuiteShape $suiteShape
    ): void {
        echo PHP_EOL . $this->render($pyramidSummary, $targetEvaluation, $suiteShape) . PHP_EOL;
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
            ...$this->renderPyramid($targetEvaluation),
            '',
            'Kind          Tests   Actual   Target      Status',
        ];

        foreach (TestKind::ordered() as $testKind) {
            $lines[] = $this->renderStatusRow($pyramidSummary, $targetEvaluation, $testKind);
        }

        $lines[] = '';
        $lines[] = sprintf('Total: %d tests', $pyramidSummary->total);
        $lines[] = '';
        $lines[] = $suiteShape->verdict;

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return list<string>
     */
    private function renderPyramid(TargetEvaluation $targetEvaluation): array
    {
        $levels     = [
            TestKind::E2E,
            TestKind::Integration,
            TestKind::Functional,
            TestKind::Unit,
        ];
        $tierWidths = [7, 19, 29, 43];
        $baseWidth  = $tierWidths[3] + 2;
        $lines      = [];

        foreach ($levels as $index => $testKind) {
            $lines[] = $this->center(
                $this->tierTop($tierWidths[$index], $tierWidths[$index - 1] ?? null),
                $baseWidth,
            );
            $lines[] = $this->center(
                '│' . $this->padCentered(
                    $testKind->label() . ' ' . $targetEvaluation->status($testKind)->symbol(),
                    $tierWidths[$index],
                ) . '│',
                $baseWidth,
            );
        }

        $lines[] = '╰' . $this->repeat('─', $tierWidths[3]) . '╯';

        return $lines;
    }

    private function renderStatusRow(
        PyramidSummary $pyramidSummary,
        TargetEvaluation $targetEvaluation,
        TestKind $testKind
    ): string {
        $status = $targetEvaluation->status($testKind);

        return sprintf(
            '%-12s %6d %8s   %-10s   %s',
            $testKind->label(),
            $pyramidSummary->count($testKind),
            sprintf('%4.1f%%', $pyramidSummary->percentage($testKind)),
            $status->label(),
            $status->symbol(),
        );
    }

    private function center(string $text, int $width): string
    {
        $padding = max(0, $width - $this->visibleLength($text));
        $left    = intdiv($padding, 2);

        return $this->repeat(' ', $left) . $text;
    }

    private function padCentered(string $text, int $width): string
    {
        $padding = max(0, $width - $this->visibleLength($text));
        $left    = intdiv($padding, 2);

        return $this->repeat(' ', $left) . $text . $this->repeat(' ', $padding - $left);
    }

    private function tierTop(int $innerWidth, ?int $previousInnerWidth): string
    {
        if ($previousInnerWidth === null) {
            return '╭' . $this->repeat('─', $innerWidth) . '╮';
        }

        $outerWidth         = $innerWidth + 2;
        $previousOuterWidth = $previousInnerWidth + 2;
        $previousStart      = intdiv($outerWidth - $previousOuterWidth, 2);
        $leftWidth          = $previousStart - 1;
        $middleWidth        = $previousOuterWidth - 2;
        $rightWidth         = $outerWidth - $previousStart - $previousOuterWidth - 1;

        return '╭'
            . $this->repeat('─', $leftWidth)
            . '┴'
            . $this->repeat('─', $middleWidth)
            . '┴'
            . $this->repeat('─', $rightWidth)
            . '╮';
    }

    private function repeat(string $text, int $count): string
    {
        return implode('', array_fill(0, $count, $text));
    }

    private function visibleLength(string $text): int
    {
        return preg_match_all('/./us', $text) ?: strlen($text);
    }
}
