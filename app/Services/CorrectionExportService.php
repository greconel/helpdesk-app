<?php

namespace App\Services;

use App\Models\AiCorrectionLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CorrectionExportService
{
    /**
     * Genereer CSV export van alle AI-correcties
     */
    public function generateCsv(): string
    {
        $corrections = $this->getAllCorrections();
        $rows = $this->generateCsvRows($corrections);

        return $this->formatCsv($rows);
    }

    /**
     * Haal alle correcties op met gerelateerde data
     */
    private function getAllCorrections(): Collection
    {
        return AiCorrectionLog::with([
            'ticket:id,ticket_number,subject,customer_id',  // ← customer_id toegevoegd
            'ticket.customer:id,name,email',
            'agent:id,name'
        ])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Genereer CSV rijen
     */
    private function generateCsvRows(Collection $corrections): array
    {
        $rows = [
            $this->generateCsvHeader()
        ];

        foreach ($corrections as $correction) {
            $rows[] = $this->generateCsvRow($correction);
        }

        return $rows;
    }

    /**
     * CSV header rij
     */
    private function generateCsvHeader(): array
    {
        return [
            'Ticketnummer',
            'Datum',
            'Klantnaam',
            'Email',
            'Subject',
            'AI Impact',
            'AI Labels',
            'Agent Impact',
            'Agent Labels',
            'Agent',
            'Correctietype'
        ];
    }

    /**
     * CSV rij voor één correctie
     */
    private function generateCsvRow(AiCorrectionLog $correction): array
    {
        $ticket = $correction->ticket;
        $customer = $correction->ticket?->customer;
        $agent = $correction->agent;

        return [
            $ticket?->ticket_number ?? 'Onbekend',
            $correction->created_at ? $correction->created_at->format('d-m-Y H:i') : 'Onbekend',
            $customer?->name ?? 'Onbekend',
            $customer?->email ?? 'Onbekend',
            $ticket?->subject ?? 'Onbekend',
            $correction->ai_impact ?? 'Niet ingesteld',
            $this->formatLabelsForCsv($correction->ai_labels),
            $correction->agent_impact ?? 'Niet ingesteld',
            $this->formatLabelsForCsv($correction->agent_labels),
            $agent?->name ?? 'Onbekend',
            $this->translateCorrectionType($correction->correction_type)
        ];
    }

    /**
     * Format labels array voor CSV
     */
    private function formatLabelsForCsv(?array $labels): string
    {
        if (empty($labels)) {
            return '';
        }
        return implode('; ', $labels);
    }

    /**
     * Format rijen als CSV string met proper escaping
     */
    private function formatCsv(array $rows): string
    {
        $output = '';
        foreach ($rows as $row) {
            foreach ($row as $field) {
                $output .= $this->escapeCsvField($field) . ',';
            }
            $output = rtrim($output, ',') . "\r\n";
        }
        return $output;
    }

    /**
     * Escape CSV veld (quotes en komma's)
     */
    private function escapeCsvField(string $field): string
    {
        if (str_contains($field, ',') || str_contains($field, '"') || str_contains($field, "\n")) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }

    /**
     * Vertaal correctietype naar Nederlands
     */
    private function translateCorrectionType(string $type): string
    {
        return match ($type) {
            'impact_only' => 'Alleen impact',
            'labels_only' => 'Alleen labels',
            'both' => 'Impact en labels',
            default => $type,
        };
    }

    /**
     * Genereer bestandsnaam
     */
    public function generateFilename(): string
    {
        return sprintf(
            'AI-Correcties_%s.csv',
            Carbon::now()->format('Y-m-d_His')
        );
    }

    /**
     * Genereer samenvattingsstatistieken
     */
    public function generateSummary(): array
    {
        $corrections = $this->getAllCorrections();
        $typeStats = $corrections->groupBy('correction_type')->map->count();
        $labelStats = $this->calculateLabelStats($corrections);

        return [
            'total_corrections' => $corrections->count(),
            'impact_only' => $typeStats->get('impact_only', 0),
            'labels_only' => $typeStats->get('labels_only', 0),
            'both' => $typeStats->get('both', 0),
            'top_labels' => array_slice($labelStats, 0, 10, true),
        ];
    }

    /**
     * Bereken statistieken per label
     */
    private function calculateLabelStats(Collection $corrections): array
    {
        $labelStats = [];

        foreach ($corrections as $correction) {
            $aiLabels = $correction->ai_labels ?? [];
            foreach ($aiLabels as $label) {
                $labelStats[$label] = ($labelStats[$label] ?? 0) + 1;
            }
        }

        arsort($labelStats);
        return $labelStats;
    }
}

