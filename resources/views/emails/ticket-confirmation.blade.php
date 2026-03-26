<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket bevestiging</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #2563eb; padding: 32px 40px; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: #bfdbfe; margin: 6px 0 0; font-size: 14px; }
        .body { padding: 32px 40px; }
        .ticket-badge { display: inline-block; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-weight: 700; font-size: 16px; padding: 8px 18px; border-radius: 6px; margin-bottom: 24px; }
        .section-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 6px; }
        .subject { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 20px; }
        .description-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px 20px; color: #374151; font-size: 14px; line-height: 1.7; margin-bottom: 28px; }
        .info-row { display: flex; gap: 40px; margin-bottom: 8px; }
        .info-item .label { font-size: 12px; color: #6b7280; margin-bottom: 2px; }
        .info-item .value { font-size: 14px; color: #111827; font-weight: 600; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 40px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Helpdesk — Ticket ontvangen</h1>
            <p>Uw verzoek is geregistreerd. We nemen zo snel mogelijk contact op.</p>
        </div>
        <div class="body">
            <div class="ticket-badge">{{ $ticket->ticket_number }}</div>

            <div class="section-label">Onderwerp</div>
            <div class="subject">{{ $ticket->subject }}</div>

            <div class="section-label">Uw vraag / probleem</div>
            <div class="description-box">
                @if($ticket->source === 'email')
                    {!! $ticket->description !!}
                @else
                    {!! nl2br(e($ticket->description)) !!}
                @endif
            </div>

            <div class="info-row">
                <div class="info-item">
                    <div class="label">Aangemaakt op</div>
                    <div class="value">{{ $ticket->created_at->setTimezone('Europe/Brussels')->format('d-m-Y H:i') }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Status</div>
                    <div class="value">Nieuw</div>
                </div>
            </div>
        </div>
        <div class="footer">
            Dit is een automatisch bericht van de Helpdesk. Beantwoord deze e-mail om een reactie toe te voegen aan uw ticket.
        </div>
    </div>
</body>
</html>