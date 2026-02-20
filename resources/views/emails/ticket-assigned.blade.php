<x-mail::message>
# Ticket aan jou toegewezen

Hallo {{ $ticket->agent->name }},

Ticket **{{ $ticket->ticket_number }}** is aan jou toegewezen.

**Onderwerp:** {{ $ticket->subject }}
**Klant:** {{ $ticket->customer->name }}
**Impact:** {{ $ticket->impact ? ucfirst($ticket->impact) : 'Niet ingesteld' }}

<x-mail::button :url="route('tickets.show', $ticket)">
Ticket bekijken
</x-mail::button>

Met vriendelijke groet,
Helpdesk Systeem
</x-mail::message>