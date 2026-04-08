<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Customer;
use App\Models\AiCorrectionLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_route_requires_authentication()
    {
        $response = $this->get(route('corrections.export'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_export_route()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('corrections.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    public function test_export_returns_correct_filename_format()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('corrections.export'));

        $this->assertTrue(
            str_contains($response->headers->get('content-disposition'), 'AI-Correcties_')
        );
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition'), '.csv')
        );
    }

    public function test_export_csv_contains_correction_details()
    {
        $user = User::factory()->create();
        $agent = User::factory()->create(['name' => 'Agent Smith']);
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $ticket = Ticket::factory()->create([
            'ticket_number' => '#0042',
            'subject' => 'Critical Bug Report',
            'customer_id' => $customer->id
        ]);

        AiCorrectionLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'ai_impact' => 'high',
            'ai_labels' => ['bug', 'critical'],
            'agent_impact' => 'medium',
            'agent_labels' => ['feature-request'],
            'ticket_subject' => 'Critical Bug Report',
            'correction_type' => 'both',
        ]);

        $response = $this->actingAs($user)->get(route('corrections.export'));

        $content = $response->getContent();

        $this->assertStringContainsString('#0042', $content);
        $this->assertStringContainsString('Critical Bug Report', $content);
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('john@example.com', $content);
        $this->assertStringContainsString('Agent Smith', $content);
        $this->assertStringContainsString('high', $content);
        $this->assertStringContainsString('medium', $content);
        $this->assertStringContainsString('bug', $content);
        $this->assertStringContainsString('critical', $content);
    }

    public function test_export_csv_contains_headers()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('corrections.export'));
        $content = $response->getContent();

        $this->assertStringContainsString('Ticketnummer', $content);
        $this->assertStringContainsString('Datum', $content);
        $this->assertStringContainsString('Klantnaam', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('Subject', $content);
        $this->assertStringContainsString('AI Impact', $content);
        $this->assertStringContainsString('AI Labels', $content);
        $this->assertStringContainsString('Agent Impact', $content);
        $this->assertStringContainsString('Agent Labels', $content);
        $this->assertStringContainsString('Agent', $content);
        $this->assertStringContainsString('Correctietype', $content);
    }

    public function test_export_csv_format_is_valid()
    {
        $user = User::factory()->create();
        $agent = User::factory()->create();
        $customer = Customer::factory()->create();

        $ticket1 = Ticket::factory()->create(['customer_id' => $customer->id]);
        $ticket2 = Ticket::factory()->create(['customer_id' => $customer->id]);

        AiCorrectionLog::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $agent->id,
            'correction_type' => 'impact_only',
        ]);

        AiCorrectionLog::create([
            'ticket_id' => $ticket2->id,
            'user_id' => $agent->id,
            'correction_type' => 'labels_only',
        ]);

        $response = $this->actingAs($user)->get(route('corrections.export'));
        $content = $response->getContent();
        $lines = explode("\r\n", trim($content));

        // First line is header
        $headerFields = str_getcsv($lines[0]);
        $this->assertCount(11, $headerFields);

        // Should have header + 2 data rows
        $this->assertGreaterThanOrEqual(2, count($lines) - 1);
    }

    public function test_export_is_downloadable()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('corrections.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_overview_page_contains_export_button()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('overview'));

        $response->assertStatus(200);
        $response->assertSee('AI-Correcties Export');
        $response->assertSee(route('corrections.export'));
        $response->assertSee('Download Export');
    }

    public function test_export_with_special_characters()
    {
        $user = User::factory()->create();
        $agent = User::factory()->create();
        $customer = Customer::factory()->create([
            'name' => 'Customer, Inc.',
            'email' => 'test@example.com'
        ]);

        $ticket = Ticket::factory()->create([
            'ticket_number' => '#0001',
            'subject' => 'Subject with "quotes" and commas',
            'customer_id' => $customer->id
        ]);

        AiCorrectionLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'correction_type' => 'both',
        ]);

        $response = $this->actingAs($user)->get(route('corrections.export'));
        $content = $response->getContent();

        // Should properly escape special characters
        $this->assertStringContainsString('"Customer, Inc."', $content);
        $this->assertStringContainsString('"Subject with ""quotes"" and commas"', $content);
    }
}
