<?php

namespace Database\Seeders;

use App\Events\TicketCreated;
use App\Models\Customer;
use App\Models\Label;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        config(['mail.default' => 'log']);

        // Verwijder bestaande users eerst
        \App\Models\User::whereIn('email', [
            'nel@support.be',
            'baziel@support.be',
            'alex@support.be',
            'kevin@support.be',
        ])->delete();

        // ── 1. Users ────────────────────────────────────────────────
        $nel = User::create([
            'name'     => 'Nel',
            'email'    => 'nel@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role'     => 'agent',
        ]);

        $baziel = User::create([
            'name'     => 'Baziel',
            'email'    => 'baziel@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role'     => 'agent',
        ]);

        $alexander = User::create([
            'name'     => 'Alexander',
            'email'    => 'alex@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role'     => 'agent',
        ]);

        $kevin = User::create([
            'name'     => 'Kevin',
            'email'    => 'kevin@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role'     => 'agent',
        ]);

        // ── 2. Labels ───────────────────────────────────────────────
        $bug          = Label::create(['name' => 'bug']);
        $onderzoek    = Label::create(['name' => 'onderzoek']);
        $feature      = Label::create(['name' => 'feature request']);
        $nietVoorOns  = Label::create(['name' => 'eigenlijk niet voor ons']);

        // ── 3. Customers ────────────────────────────────────────────
        $techcorp = Customer::create([
            'name'  => 'TechCorp BV',
            'email' => 'info@techcorp.be',
            'phone' => '+32 9 123 45 67',
        ]);

        $jan = Customer::create([
            'name'  => 'Jan Janssen',
            'email' => 'jan.janssen@mail.com',
            'phone' => '+32 498 12 34 56',
        ]);

        $media = Customer::create([
            'name'  => 'MediaDesign Studio',
            'email' => 'contact@mediadesign.be',
            'phone' => '+32 3 987 65 43',
        ]);

        $sara = Customer::create([
            'name'  => 'Sara Vermeulen',
            'email' => 'sara.v@gmail.com',
            'phone' => null,
        ]);

        $webshop = Customer::create([
            'name'  => 'WebShop Solutions',
            'email' => 'support@webshop-solutions.be',
            'phone' => '+32 2 555 77 88',
        ]);

        $piet = Customer::create([
            'name'  => 'Piet De Smet',
            'email' => 'piet.desmet@hotmail.com',
            'phone' => '+32 476 88 99 00',
        ]);

        // ── 4. Tickets ──────────────────────────────────────────────
        // Helper: maak ticket aan + fire event met AI analyse, GEEN bevestigingsmail
        $maak = function (array $data, array $labelIds = []) {
            $ticket = Ticket::create($data + [
                'ticket_number' => Ticket::generateTicketNumber(),
                'status'        => 'new',
                'impact'        => null,
            ]);

            if (!empty($labelIds)) {
                $ticket->labels()->sync($labelIds);
            }

            // true = AI analyse, false = geen bevestigingsmail
            event(new TicketCreated($ticket, false));

            return $ticket;
        };

        // Ticket 1 — Kritieke bug, niet toegewezen
        $maak([
            'subject'     => 'Database connectie valt constant weg',
            'description' => 'Sinds vanmorgen 08:00 krijgen we constant timeouts bij het openen van de applicatie. Onze medewerkers kunnen niet werken. Dit is zeer urgent!',
            'status'      => 'new',
            'customer_id' => $techcorp->id,
            'assigned_to' => null,
        ]);

        // Ticket 2 — Feature request, in behandeling
        $maak([
            'subject'     => 'Export functie naar Excel toevoegen',
            'description' => 'We willen graag onze rapportages kunnen exporteren naar Excel formaat. Dit zou ons veel tijd besparen bij de maandelijkse analyses.',
            'status'      => 'in_progress',
            'customer_id' => $media->id,
            'assigned_to' => $nel->id,
        ]);

        // Ticket 3 — Bug + onderzoek, on hold
        $maak([
            'subject'     => 'Facturatie toont verkeerde bedragen',
            'description' => 'Bij sommige facturen zie ik afwijkende bedragen staan. Kan iemand dit nakijken? Het verschil is ongeveer 2-3%.',
            'status'      => 'on_hold',
            'customer_id' => $jan->id,
            'assigned_to' => $baziel->id,
        ]);

        // Ticket 4 — Wachtwoord reset vraag, bijna klaar
        $maak([
            'subject'     => 'Hoe kan ik mijn wachtwoord resetten?',
            'description' => 'Ik ben mijn wachtwoord vergeten en kan niet meer inloggen. Kunnen jullie me helpen?',
            'status'      => 'to_close',
            'customer_id' => $sara->id,
            'assigned_to' => $alexander->id,
        ]);

        // Ticket 5 — Gesloten, styling bug
        $maak([
            'subject'     => 'Styling probleem op mobiele versie',
            'description' => 'De knoppen zijn te klein op mijn smartphone. Kunnen jullie dit aanpassen?',
            'status'      => 'closed',
            'customer_id' => $jan->id,
            'assigned_to' => $kevin->id,
            'closed_at'   => now()->subDays(2),
        ]);

        // Ticket 6 — Externe issue
        $maak([
            'subject'     => 'Microsoft Teams integratie werkt niet',
            'description' => 'We kunnen geen berichten meer versturen via Teams. Is dit een probleem bij jullie?',
            'status'      => 'in_progress',
            'customer_id' => $webshop->id,
            'assigned_to' => $alexander->id,
        ]);

        // Ticket 7 — Betalingen geblokkeerd, hoge prioriteit
        $maak([
            'subject'     => 'Betalingen worden niet verwerkt',
            'description' => 'Sinds gisteren worden online betalingen niet meer correct verwerkt. Klanten klagen dat ze dubbel betalen. Dit moet ASAP opgelost worden!',
            'status'      => 'in_progress',
            'customer_id' => $webshop->id,
            'assigned_to' => $baziel->id,
        ]);

        // Ticket 8 — Dark mode feature request
        $maak([
            'subject'     => 'Dark mode toevoegen aan dashboard',
            'description' => 'Het zou fijn zijn als er een dark mode komt. Ik werk vaak \'s avonds en dan is het scherm erg fel. Ik wil daarvoor een icoontje rechtsboven, een soort toggle.',
            'status'      => 'new',
            'customer_id' => $piet->id,
            'assigned_to' => null,
        ]);

        // Ticket 9 — API docs vraag
        $maak([
            'subject'     => 'Waar vind ik de API documentatie?',
            'description' => 'We willen een custom integratie bouwen. Kunnen jullie me de API docs doorsturen?',
            'status'      => 'to_close',
            'customer_id' => $techcorp->id,
            'assigned_to' => $nel->id,
        ]);

        // Ticket 10 — Rapport bug, hoge impact
        $maak([
            'subject'     => 'Rapportage toont geen data voor laatste maand',
            'description' => 'Wanneer ik een rapport genereer voor januari 2026, krijg ik geen resultaten. Voor december 2025 werkt het wel perfect. Kunnen jullie dit onderzoeken?',
            'status'      => 'in_progress',
            'customer_id' => $media->id,
            'assigned_to' => $kevin->id,
        ]);
    }
}