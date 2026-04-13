<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Label;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. AGENTS ────────────────────────────────────────────────────────
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

        // ── 2. LABELS ─────────────────────────────────────────────────────────
        $bugLabel      = Label::create(['name' => 'bug']);
        $onderzoek     = Label::create(['name' => 'onderzoek']);
        $featureLabel  = Label::create(['name' => 'feature request']);
        $nietVoorOns   = Label::create(['name' => 'eigenlijk niet voor ons']);

        // ── 3. CUSTOMERS ──────────────────────────────────────────────────────
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

        $mediadesign = Customer::create([
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

        // ── 4. TICKETS ────────────────────────────────────────────────────────

        // #0001 — Kritieke bug, niet toegewezen
        $ticket1 = Ticket::create([
            'ticket_number' => '#0001',
            'subject'       => 'Database connectie valt constant weg',
            'description'   => "Sinds vanmorgen 08:00 krijgen we constant timeouts bij het openen van de applicatie. Onze medewerkers kunnen niet werken. Dit is zeer urgent!\n\nFoutmelding: SQLSTATE[HY000] [2002] Connection refused\n\nDit gebeurt elke 5-10 minuten en duurt telkens 2-3 minuten.",
            'status'        => 'new',
            'impact'        => 'high',
            'customer_id'   => $techcorp->id,
            'assigned_to'   => null,
            'source'        => 'email',
        ]);
        $ticket1->labels()->attach([$bugLabel->id]);

        // #0002 — Feature request, in behandeling door Nel
        $ticket2 = Ticket::create([
            'ticket_number' => '#0002',
            'subject'       => 'Export functie naar Excel toevoegen',
            'description'   => "We willen graag onze rapportages kunnen exporteren naar Excel-formaat. Dit zou ons veel tijd besparen bij de maandelijkse analyses.\n\nConcreet: een knop 'Exporteer naar Excel' op de rapportagepagina, met alle gefilterde data.",
            'status'        => 'in_progress',
            'impact'        => 'medium',
            'customer_id'   => $mediadesign->id,
            'assigned_to'   => $nel->id,
            'source'        => 'web',
        ]);
        $ticket2->labels()->attach([$featureLabel->id]);

        // #0003 — Onderzoek nodig, on hold bij Baziel
        $ticket3 = Ticket::create([
            'ticket_number' => '#0003',
            'subject'       => 'Facturatie toont verkeerde bedragen',
            'description'   => "Bij sommige facturen zie ik afwijkende bedragen staan. Kan iemand dit nakijken? Het verschil is ongeveer 2-3%.\n\nIk heb het gemerkt op facturen van deze maand. Vorige maand was alles correct.\n\nBijlage: factuur #2024-089 en #2024-091 tonen het probleem.",
            'status'        => 'on_hold',
            'impact'        => 'medium',
            'customer_id'   => $jan->id,
            'assigned_to'   => $baziel->id,
            'source'        => 'email',
        ]);
        $ticket3->labels()->attach([$bugLabel->id, $onderzoek->id]);

        // #0004 — Eenvoudige vraag, bijna afgerond
        $ticket4 = Ticket::create([
            'ticket_number' => '#0004',
            'subject'       => 'Hoe kan ik mijn wachtwoord resetten?',
            'description'   => "Ik ben mijn wachtwoord vergeten en kan niet meer inloggen. Kunnen jullie me helpen?\n\nIk heb al de 'wachtwoord vergeten' knop geprobeerd maar ontvang geen e-mail.",
            'status'        => 'to_close',
            'impact'        => 'low',
            'customer_id'   => $sara->id,
            'assigned_to'   => $alexander->id,
            'source'        => 'web',
        ]);

        // #0005 — Gesloten ticket
        $ticket5 = Ticket::create([
            'ticket_number' => '#0005',
            'subject'       => 'Styling probleem op mobiele versie',
            'description'   => "De knoppen zijn te klein op mijn smartphone en overlappen soms met de tekst eronder. Dit maakt de app moeilijk te gebruiken onderweg.\n\nGetest op iPhone 14 en Samsung Galaxy S23.",
            'status'        => 'closed',
            'impact'        => 'low',
            'customer_id'   => $jan->id,
            'assigned_to'   => $kevin->id,
            'closed_at'     => now()->subDays(3),
            'source'        => 'web',
        ]);
        $ticket5->labels()->attach([$bugLabel->id]);

        // #0006 — Externe issue
        $ticket6 = Ticket::create([
            'ticket_number' => '#0006',
            'subject'       => 'Microsoft Teams integratie werkt niet',
            'description'   => "We kunnen geen berichten meer versturen via de Teams-integratie in jullie platform. Is dit een probleem bij jullie of bij Microsoft?\n\nSinds gisterenavond ~18u.",
            'status'        => 'in_progress',
            'impact'        => 'medium',
            'customer_id'   => $webshop->id,
            'assigned_to'   => $alexander->id,
            'source'        => 'email',
        ]);
        $ticket6->labels()->attach([$nietVoorOns->id, $onderzoek->id]);

        // #0007 — High impact bug
        $ticket7 = Ticket::create([
            'ticket_number' => '#0007',
            'subject'       => 'Betalingen worden niet verwerkt',
            'description'   => "Sinds gisteren worden online betalingen niet meer correct verwerkt. Klanten melden dat ze dubbel worden aangerekend.\n\nWe hebben al 12 klachten ontvangen. Dit moet ASAP opgelost worden — we derven significant omzet.\n\nBetalingsgateway: Mollie. Foutcode: PAYMENT_DUPLICATE_REFERENCE.",
            'status'        => 'in_progress',
            'impact'        => 'high',
            'customer_id'   => $webshop->id,
            'assigned_to'   => $baziel->id,
            'source'        => 'email',
        ]);
        $ticket7->labels()->attach([$bugLabel->id]);

        // #0008 — Feature request, niet toegewezen
        $ticket8 = Ticket::create([
            'ticket_number' => '#0008',
            'subject'       => 'Dark mode toevoegen aan het dashboard',
            'description'   => "Het zou fijn zijn als er een dark mode beschikbaar komt. Ik werk vaak 's avonds en dan is het scherm erg fel.\n\nEen toggle-knop bovenaan de pagina zou perfect zijn.",
            'status'        => 'new',
            'impact'        => 'low',
            'customer_id'   => $piet->id,
            'assigned_to'   => null,
            'source'        => 'web',
        ]);
        $ticket8->labels()->attach([$featureLabel->id]);

        // #0009 — Documentatievraag, bijna klaar
        $ticket9 = Ticket::create([
            'ticket_number' => '#0009',
            'subject'       => 'Waar vind ik de API documentatie?',
            'description'   => "We willen een custom integratie bouwen met jullie platform. Kunnen jullie me de API-documentatie doorsturen?\n\nSpecifiek zoeken we endpoints voor: klantenbeheer, facturatie en rapportage.",
            'status'        => 'to_close',
            'impact'        => 'low',
            'customer_id'   => $techcorp->id,
            'assigned_to'   => $nel->id,
            'source'        => 'web',
        ]);

        // #0010 — Complexe bug
        $ticket10 = Ticket::create([
            'ticket_number' => '#0010',
            'subject'       => 'Rapportage toont geen data voor januari 2026',
            'description'   => "Wanneer ik een rapport genereer voor januari 2026 krijg ik geen resultaten te zien. Voor december 2025 werkt het perfect.\n\nIk heb al de cache geleegd en andere browsers geprobeerd — zelfde resultaat.\n\nKan dit een probleem zijn met de jaarwisseling in jullie systeem?",
            'status'        => 'in_progress',
            'impact'        => 'high',
            'customer_id'   => $mediadesign->id,
            'assigned_to'   => $kevin->id,
            'source'        => 'email',
        ]);
        $ticket10->labels()->attach([$bugLabel->id, $onderzoek->id]);

        // #0011 — Nieuw ticket, ongelabeld (goed voor AI demo)
        $ticket11 = Ticket::create([
            'ticket_number' => '#0011',
            'subject'       => 'Kunnen we een automatische herinnering instellen voor openstaande facturen?',
            'description'   => "We willen graag dat het systeem automatisch een herinneringsmail stuurt naar klanten die een openstaande factuur hebben na 14 dagen.\n\nIdeaal zou het ook fijn zijn als we de tekst van die mail zelf kunnen aanpassen.",
            'status'        => 'new',
            'impact'        => 'medium',
            'customer_id'   => $jan->id,
            'assigned_to'   => null,
            'source'        => 'email',
        ]);
        // Geen labels → goed om AI-labeling te demonstreren

        // #0012 — Urgent, net binnengekomen
        $ticket12 = Ticket::create([
            'ticket_number' => '#0012',
            'subject'       => 'Volledige applicatie onbereikbaar voor alle gebruikers',
            'description'   => "De volledige applicatie geeft een 502 Bad Gateway error voor al onze gebruikers. Niemand kan inloggen.\n\nDit is begonnen om 09:47 deze ochtend na een update die jullie zelf hebben uitgerold.\n\nWe hebben 85 medewerkers die nu niet kunnen werken.",
            'status'        => 'new',
            'impact'        => 'high',
            'customer_id'   => $techcorp->id,
            'assigned_to'   => null,
            'source'        => 'email',
        ]);
        $ticket12->labels()->attach([$bugLabel->id]);
    }
}