<x-mail::message>
# Uw ticket is aangemaakt

Beste {{ $ticket->customer->name }},

Bedankt voor uw bericht. We hebben uw ticket ontvangen en zijn ermee aan de slag.

**Ticketnummer:** {{ $ticket->ticket_number }}
**Onderwerp:** {{ $ticket->subject }}

We nemen zo snel mogelijk contact met u op.

<x-mail::button :url="route('tickets.create')">
Nog een ticket indienen
</x-mail::button>

Met vriendelijke groet,
Het Helpdesk Team
</x-mail::message>