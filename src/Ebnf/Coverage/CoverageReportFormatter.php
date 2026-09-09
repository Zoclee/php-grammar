<?php

declare(strict_types=1);

namespace PhpGrammar\Ebnf\Coverage;

final readonly class CoverageReportFormatter
{
    public function format(string $title, CoverageReport $report): string
    {
        return sprintf(
            "%s\n\nProductions:   %d / %d  (%.1f%%)\nAlternatives:  %d / %d  (%.1f%%)\n\nAttempted productions:  %d / %d\nAttempted alternatives: %d / %d\n\nUncovered productions:\n%s\n\nUncovered alternatives:\n%s\n",
            $title,
            $report->exercisedProductions(),
            $report->totalProductions(),
            $report->productionCoveragePercentage(),
            $report->exercisedAlternatives(),
            $report->totalAlternatives(),
            $report->alternativeCoveragePercentage(),
            $report->attemptedProductions(),
            $report->totalProductions(),
            $report->attemptedAlternatives(),
            $report->totalAlternatives(),
            $this->list($report->unexercisedProductions()),
            $this->list($report->unexercisedAlternatives()),
        );
    }

    /**
     * @param list<string> $items
     */
    private function list(array $items): string
    {
        if ($items === []) {
            return '- none';
        }

        return implode("\n", array_map(static fn (string $item): string => '- ' . $item, $items));
    }
}
