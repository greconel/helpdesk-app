<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Toon het formulier voor een nieuw ticket
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Sla een nieuw ticket op
     */
    public function store(Request $request)
    {
        // Validatie
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
        ], [
            'name.required' => 'Naam is verplicht',
            'email.required' => 'E-mailadres is verplicht',
            'email.email' => 'Voer een geldig e-mailadres in',
            'subject.required' => 'Onderwerp is verplicht',
            'description.required' => 'Beschrijving is verplicht',
        ]);

        DB::beginTransaction();
        try {
            // Zoek of maak klant aan
            $customer = Customer::firstOrCreate(
                ['email' => $validated['email']],
                ['name' => $validated['name']]
            );

            // Genereer ticket nummer
            $ticketNumber = $this->generateTicketNumber();

            // Maak ticket aan
            $ticket = Ticket::create([
                'ticket_number' => $ticketNumber,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'status' => 'new',
                'customer_id' => $customer->id,
            ]);

            DB::commit();

            return redirect()
                ->route('tickets.create')
                ->with('success', "Uw ticket ({$ticketNumber}) is succesvol aangemaakt. We nemen zo snel mogelijk contact met u op.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Er is iets misgegaan. Probeer het opnieuw.');
        }
    }

    /**
     * Genereer een uniek ticket nummer
     */
    private function generateTicketNumber(): string
    {
        $lastTicket = Ticket::orderBy('id', 'desc')->first();
        
        if (!$lastTicket) {
            return '#0001';
        }

        // Haal nummer uit laatste ticket (bijv. #0005 wordt 5)
        $lastNumber = (int) str_replace('#', '', $lastTicket->ticket_number);
        $newNumber = $lastNumber + 1;

        // Format met leading zeros
        return '#' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    /**
     * Toon ticket details
     */
    public function show(Ticket $ticket)
    {
        // Eager load relaties
        $ticket->load(['customer', 'agent', 'labels']);
        
        return view('tickets.show', compact('ticket'));
    }
}