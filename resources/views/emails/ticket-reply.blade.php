<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #2563eb; padding: 24px 40px; }
        .header h1 { color: #fff; margin: 0; font-size: 18px; }
        .header p { color: #bfdbfe; margin: 4px 0 0; font-size: 13px; }
        .body { padding: 28px 40px; color: #374151; font-size: 14px; line-height: 1.7; }
        .ticket-ref { display: inline-block; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-weight: 700; font-size: 13px; padding: 4px 12px; border-radius: 4px; margin-bottom: 16px; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 40px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Helpdesk — Reactie op uw ticket</h1>
            <p>{{ $agentName }} heeft gereageerd op uw ticket</p>
        </div>
        <div class="body">
            <div class="ticket-ref">{{ $ticket->ticket_number }}</div>
            <p><strong>{{ $ticket->subject }}</strong></p>
            <div>{!! $body !!}</div>
        </div>
        <div class="footer">
            Beantwoord deze e-mail om te reageren op uw ticket.
        </div>
    </div>
</body>
</html>