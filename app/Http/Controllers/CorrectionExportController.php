<?php

namespace App\Http\Controllers;

use App\Services\CorrectionExportService;

class CorrectionExportController extends Controller
{
    public function export(CorrectionExportService $service)
    {
        $csv      = $service->generateCsv();
        $summary  = $service->generateSummary();
        $filename = $service->generateFilename();

        $summaryLines  = "\r\n";
        $summaryLines .= "SAMENVATTING\r\n";
        $summaryLines .= "Totaal correcties," . $summary['total_corrections'] . "\r\n";
        $summaryLines .= "Alleen impact," . $summary['impact_only'] . "\r\n";
        $summaryLines .= "Alleen labels," . $summary['labels_only'] . "\r\n";
        $summaryLines .= "Impact en labels," . $summary['both'] . "\r\n";

        if (!empty($summary['top_labels'])) {
            $summaryLines .= "\r\nMeest gecorrigeerde labels\r\n";
            foreach ($summary['top_labels'] as $label => $count) {
                $summaryLines .= "{$label},{$count}\r\n";
            }
        }

        return response($csv . $summaryLines)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}