<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CorrectionExportService;
use App\Models\AiCorrectionLog;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CorrectionExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private CorrectionExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CorrectionExportService();
    }

    public function test_generates_csv_with_empty_corrections()
    {
        $csv = $this->service->generateCsv();

        // Should contain header
        $this->assertStringContainsString('Ticketnummer', $csv);
        $this->assertStringContainsString('Datum', $csv);
        $this->assertStringContainsString('Klantnaam', $csv);
        $this->assertStringContainsString('Email', $csv);
    }

    public function test_generates_filename_with_correct_format()
    {
        $filename = $this->service->generateFilename();

        $this->assertStringStartsWith('AI-Correcties_', $filename);
        $this->assertStringEndsWith('.csv', $filename);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}_\d{6}\.csv$/', $filename);
    }

    public function test_generates_csv_with_all_details()
    {
        // Setup: Create test data
        $customer = Customer::factory()->create([
            'name' => 'Test Klant',
            'email' => 'test@example.com'
        ]);

        $ticket = Ticket::factory()->create([
            'ticket_number' => '#0001',
            'subject' => 'Test Subject',
            'customer_id' => $customer->id
        ]);

        $agent = User::factory()->create(['name' => 'Test Agent']);

        AiCorrectionLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'ai_impact' => 'high',
            'ai_labels' => ['bug', 'urgent'],
            'agent_impact' => 'medium',
            'agent_labels' => ['feature', 'documentation'],
            'ticket_subject' => 'Test Subject',
            'correction_type' => 'both',
        ]);

        $csv = $this->service->generateCsv();

        // Assertions for CSV content
        $this->assertStringContainsString('#0001', $csv);
        $this->assertStringContainsString('Test Subject', $csv);
        $this->assertStringContainsString('Test Klant', $csv);
        $this->assertStringContainsString('test@example.com', $csv);
        $this->assertStringContainsString('high', $csv);
        $this->assertStringContainsString('medium', $csv);
        $this->assertStringContainsString('bug', $csv);
        $this->assertStringContainsString('urgent', $csv);
        $this->assertStringContainsString('feature', $csv);
        $this->assertStringContainsString('documentation', $csv);
        $this->assertStringContainsString('Test Agent', $csv);
        $this->assertStringContainsString('Impact en labels', $csv);
    }

    public function test_csv_has_correct_structure()
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);
        $agent = User::factory()->create();

        AiCorrectionLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'correction_type' => 'impact_only',
        ]);

        $csv = $this->service->generateCsv();
        $lines = explode("\r\n", trim($csv));

        // First line should be header
        $this->assertCount(11, explode(',', $lines[0]));

        // At least one data row
        $this->assertGreaterThan(1, count($lines));
    }

    public function test_labels_are_formatted_with_semicolons()
    {
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);
        $agent = User::factory()->create();

        AiCorrectionLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'ai_labels' => ['label1', 'label2', 'label3'],
            'correction_type' => 'labels_only',
        ]);

        $csv = $this->service->generateCsv();

        $this->assertStringContainsString('label1; label2; label3', $csv);
    }

    public function test_summary_statistics_are_calculated()
    {
        $agent = User::factory()->create();
        $customer = Customer::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);
            AiCorrectionLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $agent->id,
                'ai_labels' => ['bug'],
                'correction_type' => 'labels_only',
            ]);
        }

        $summary = $this->service->generateSummary();

        $this->assertEquals(3, $summary['total_corrections']);
        $this->assertEquals(3, $summary['labels_only']);
        $this->assertEquals(0, $summary['impact_only']);
        $this->assertEquals(0, $summary['both']);
        $this->assertArrayHasKey('bug', $summary['top_labels']);
        $this->assertEquals(3, $summary['top_labels']['bug']);
    }

    public function test_correction_types_are_translated()
    {
        $agent = User::factory()->create();
        $customer = Customer::factory()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);

        AiCorrectionLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'correction_type' => 'impact_only',
        ]);

        $csv = $this->service->generateCsv();
        
        $this->assertStringContainsString('Alleen impact', $csv);
    }

    public function test_csv_escapes_special_characters()
    {
        $customer = Customer::factory()->create([
            'name' => 'Customer, Inc.',
            'email' => 'test@example.com'
        ]);

        $ticket = Ticket::factory()->create([
            'ticket_number' => '#0001',
            'subject' => 'Subject with "quotes"',
            'customer_id' => $customer->id
        ]);

        $agent = User::factory()->create(['name' => 'Agent']);

        AiCorrectionLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'correction_type' => 'both',
        ]);

        $csv = $this->service->generateCsv();

        // Should properly escape quoted fields
        $this->assertStringContainsString('"Customer, Inc."', $csv);
        $this->assertStringContainsString('"Subject with ""quotes"""', $csv);
    }
}
