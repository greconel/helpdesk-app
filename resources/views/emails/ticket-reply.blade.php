<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #111827; font-size: 14px; line-height: 1.6;">

    {!! $body !!}

    <br>
    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
    <p style="color: #6b7280; font-size: 12px; margin: 0;">
        {{ $agentName }} — Helpdesk<br>
        Ticket: {{ $ticket->ticket_number }} · {{ $ticket->subject }}<br>
        Beantwoord deze e-mail om te reageren op uw ticket.
    </p>

</body>
</html>