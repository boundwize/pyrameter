<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Report;

use Boundwize\Pyrameter\PyramidSummary;
use Boundwize\Pyrameter\Target\TargetEvaluation;
use Boundwize\Pyrameter\TestKind;

use function array_fill;
use function array_map;
use function array_values;
use function count;
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
        $statusTable = $this->renderStatusTable($pyramidSummary, $targetEvaluation);

        $lines = [
            '=========',
            'Pyrameter',
            '=========',
            '',
            sprintf('%-7s %s', 'Shape:', $suiteShape->name),
            sprintf('%-7s %s', 'Result:', $targetEvaluation->allPassed()
                ? 'Passed ✓' : 'Violated ⚠'),
            '',
            ...$this->renderPyramid($targetEvaluation, $this->visibleLength($statusTable[0])),
            '',
            ...$statusTable,
        ];

        $lines[] = '';
        $lines[] = sprintf('Total: %d tests', $pyramidSummary->total);
        $lines[] = '';
        $lines[] = $suiteShape->verdict;

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return list<string>
     */
    private function renderPyramid(TargetEvaluation $targetEvaluation, int $width): array
    {
        $levels      = [
            TestKind::E2E,
            TestKind::Integration,
            TestKind::Functional,
            TestKind::Unit,
        ];
        $blockWidths = [1, 5, 9, 13];
        $maxBlock    = $blockWidths[3];

        $prefixes = [];

        foreach ($levels as $index => $testKind) {
            $indent     = intdiv($maxBlock - $blockWidths[$index], 2);
            $block      = $index === 0 ? '▲' : $this->repeat('▄', $blockWidths[$index]);
            $prefixes[] = $this->repeat(' ', $indent) . $block . '  ' . $testKind->label();
        }

        $prefixWidth = max(array_map($this->visibleLength(...), $prefixes));
        $gap         = 2;
        $lineWidth   = $prefixWidth + $gap + 1;
        $leftPad     = intdiv(max(0, $width - $lineWidth), 2);

        $lines = [];

        foreach ($levels as $index => $testKind) {
            $symbol  = $targetEvaluation->status($testKind)->symbol();
            $lines[] = $this->repeat(' ', $leftPad)
                . $this->padForColumn($prefixes[$index], $prefixWidth, 'left')
                . $this->repeat(' ', $gap)
                . $symbol;
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function renderStatusTable(
        PyramidSummary $pyramidSummary,
        TargetEvaluation $targetEvaluation
    ): array {
        $rows = [];

        foreach (TestKind::ordered() as $testKind) {
            $status = $targetEvaluation->status($testKind);
            $rows[] = [
                $testKind->label(),
                (string) $pyramidSummary->count($testKind),
                sprintf('%.1f%%', $pyramidSummary->percentage($testKind)),
                $status->ignored ? $status->label() : $status->label() . ' ' . $status->symbol(),
            ];
        }

        return $this->renderTable(
            ['KIND', 'TESTS', 'ACTUAL', 'TARGET'],
            $rows,
            ['left', 'right', 'right', 'right'],
        );
    }

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     * @param list<string> $alignments
     * @return list<string>
     */
    private function renderTable(array $headers, array $rows, array $alignments): array
    {
        $widths = [];

        foreach ($headers as $index => $header) {
            $widths[$index] = $this->visibleLength($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $index => $cell) {
                $widths[$index] = max($widths[$index], $this->visibleLength($cell));
            }
        }

        $widths = array_values($widths);

        $lines   = [];
        $lines[] = $this->renderTableBorder($widths, '+', '+', '+', '=');
        $lines[] = $this->renderTableRow($headers, $widths, array_fill(0, count($headers), 'center'));
        $lines[] = $this->renderTableBorder($widths, '+', '+', '+', '=');

        foreach ($rows as $index => $row) {
            $lines[] = $this->renderTableRow($row, $widths, $alignments);

            if ($index < count($rows) - 1) {
                $lines[] = $this->renderTableBorder($widths, '+', '+', '+');
            }
        }

        $lines[] = $this->renderTableBorder($widths, '+', '+', '+');

        return $lines;
    }

    /**
     * @param list<int> $widths
     */
    private function renderTableBorder(
        array $widths,
        string $left,
        string $join,
        string $right,
        string $fill = '-'
    ): string {
        $segments = [];

        foreach ($widths as $width) {
            $segments[] = $this->repeat($fill, $width + 2);
        }

        return $left . implode($join, $segments) . $right;
    }

    /**
     * @param list<string> $cells
     * @param list<int> $widths
     * @param list<string> $alignments
     */
    private function renderTableRow(array $cells, array $widths, array $alignments): string
    {
        $line = '|';

        foreach ($cells as $index => $cell) {
            $line .= ' '
                . $this->padForColumn($cell, $widths[$index], $alignments[$index] ?? 'left')
                . ' |';
        }

        return $line;
    }

    private function padForColumn(string $text, int $width, string $alignment): string
    {
        $padding = max(0, $width - $this->visibleLength($text));

        return match ($alignment) {
            'center' => $this->repeat(' ', intdiv($padding, 2))
                . $text
                . $this->repeat(' ', $padding - intdiv($padding, 2)),
            'right' => $this->repeat(' ', $padding) . $text,
            default => $text . $this->repeat(' ', $padding),
        };
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
