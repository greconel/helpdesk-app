<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 15px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .mail-body {
            max-width: 680px;
            margin: 0 auto;
            padding: 32px 24px;
        }
        p {
            margin: 0 0 16px;
            line-height: 1.6;
        }
        .signature {
            margin-top: 24px;
            color: #444;
        }
        .divider {
            border: none;
            border-top: 1px solid #d1d5db;
            margin: 28px 0 20px;
        }
        .ticket-meta {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <div class="mail-body">

        {{-- Berichttekst van de agent --}}
        <div>{!! $body !!}</div>

        {{-- Handtekening --}}
        <div class="signature">
            <p>Met vriendelijke groeten,<br>
            <strong>{{ $agentName }}</strong><br>
            Helpdesk</p>
        </div>

        {{-- Scheidingslijn + ticket-referentie (zoals Outlook) --}}
        <hr class="divider">
        <div class="ticket-meta">
            <strong>Ticket:</strong> {{ $ticket->ticket_number }} — {{ $ticket->subject }}<br>
            <strong>Klant:</strong> {{ $ticket->customer->name }} &lt;{{ $ticket->customer->email }}&gt;
        </div>

    </div>
</body>
</html>