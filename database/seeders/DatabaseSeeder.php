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
        // 1. Users - BLIJVEN HETZELFDE (Nel, Baziel, Alexander, Kevin)
        $nel = User::create([
            'name' => 'Nel',
            'email' => 'nel@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role' => 'agent'
        ]);

        $baziel = User::create([
            'name' => 'Baziel',
            'email' => 'baziel@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role' => 'agent'
        ]);

        $alexander = User::create([
            'name' => 'Alexander',
            'email' => 'alex@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role' => 'agent'
        ]);

        $kevin = User::create([
            'name' => 'Kevin',
            'email' => 'kevin@support.be',
            'password' => Hash::make('wachtwoord123'),
            'role' => 'agent'
        ]);

        // 2. Labels aanmaken
        $bugLabel = Label::create(['name' => 'bug']);
        $onderzoekLabel = Label::create(['name' => 'onderzoek']);
        $featureLabel = Label::create(['name' => 'feature request']);
        $nietVoorOnsLabel = Label::create(['name' => 'eigenlijk niet voor ons']);
        $urgentLabel = Label::create(['name' => 'urgent']);
        $documentatieLabel = Label::create(['name' => 'documentatie']);

        // 3. Customers aanmaken - verschillende bedrijven en particulieren
        $customer1 = Customer::create([
            'name' => 'TechCorp BV',
            'email' => 'info@techcorp.be',
            'phone' => '+32 9 123 45 67'
        ]);

        $customer2 = Customer::create([
            'name' => 'Jan Janssen',
            'email' => 'jan.janssen@mail.com',
            'phone' => '+32 498 12 34 56'
        ]);

        $customer3 = Customer::create([
            'name' => 'MediaDesign Studio',
            'email' => 'contact@mediadesign.be',
            'phone' => '+32 3 987 65 43'
        ]);

        $customer4 = Customer::create([
            'name' => 'Sara Vermeulen',
            'email' => 'sara.v@gmail.com',
            'phone' => null // Geen telefoon
        ]);

        $customer5 = Customer::create([
            'name' => 'WebShop Solutions',
            'email' => 'support@webshop-solutions.be',
            'phone' => '+32 2 555 77 88'
        ]);

        $customer6 = Customer::create([
            'name' => 'Piet De Smet',
            'email' => 'piet.desmet@hotmail.com',
            'phone' => '+32 476 88 99 00'
        ]);

        // 4. Tickets aanmaken - 10 realistische scenario's
        
        // TICKET 1: Kritieke bug, nog niet toegewezen
        $ticket1 = Ticket::create([
            'ticket_number' => '#0001',
            'subject' => 'Database connectie valt constant weg',
            'description' => "Sinds vanmorgen 08:00 krijgen we constant timeouts bij het openen van de applicatie. Onze medewerkers kunnen niet werken. Dit is zeer urgent!",
            'status' => 'new',
            'impact' => 'high',
            'customer_id' => $customer1->id,
            'assigned_to' => null // Nog niet toegewezen
        ]);
        $ticket1->labels()->attach([$bugLabel->id, $urgentLabel->id]);

        // TICKET 2: Feature request in behandeling door Nel
        $ticket2 = Ticket::create([
            'ticket_number' => '#0002',
            'subject' => 'Export functie naar Excel toevoegen',
            'description' => "We willen graag onze rapportages kunnen exporteren naar Excel formaat. Dit zou ons veel tijd besparen bij de maandelijkse analyses.",
            'status' => 'in_progress',
            'impact' => 'medium',
            'customer_id' => $customer3->id,
            'assigned_to' => $nel->id
        ]);
        $ticket2->labels()->attach([$featureLabel->id]);

        // TICKET 3: Onderzoek nodig, on hold door Baziel
        $ticket3 = Ticket::create([
            'ticket_number' => '#0003',
            'subject' => 'Facturatie toont verkeerde bedragen',
            'description' => "Bij sommige facturen zie ik afwijkende bedragen staan. Kan iemand dit nakijken? Het verschil is ongeveer 2-3%.",
            'status' => 'on_hold',
            'impact' => 'medium',
            'customer_id' => $customer2->id,
            'assigned_to' => $baziel->id
        ]);
        $ticket3->labels()->attach([$bugLabel->id, $onderzoekLabel->id]);

        // TICKET 4: Eenvoudige vraag, bijna klaar
        $ticket4 = Ticket::create([
            'ticket_number' => '#0004',
            'subject' => 'Hoe kan ik mijn wachtwoord resetten?',
            'description' => "Ik ben mijn wachtwoord vergeten en kan niet meer inloggen. Kunnen jullie me helpen?",
            'status' => 'to_close',
            'impact' => 'low',
            'customer_id' => $customer4->id,
            'assigned_to' => $alexander->id
        ]);
        $ticket4->labels()->attach([$documentatieLabel->id]);

        // TICKET 5: Gesloten ticket
        $ticket5 = Ticket::create([
            'ticket_number' => '#0005',
            'subject' => 'Styling probleem op mobiele versie',
            'description' => "De knoppen zijn te klein op mijn smartphone. Kunnen jullie dit aanpassen?",
            'status' => 'closed',
            'impact' => 'low',
            'customer_id' => $customer2->id, // Zelfde klant als ticket 3
            'assigned_to' => $kevin->id,
            'closed_at' => now()->subDays(2)
        ]);
        $ticket5->labels()->attach([$bugLabel->id]);

        // TICKET 6: Externe issue, niet voor ons
        $ticket6 = Ticket::create([
            'ticket_number' => '#0006',
            'subject' => 'Microsoft Teams integratie werkt niet',
            'description' => "We kunnen geen berichten meer versturen via Teams. Is dit een probleem bij jullie?",
            'status' => 'in_progress',
            'impact' => 'medium',
            'customer_id' => $customer5->id,
            'assigned_to' => $alexander->id
        ]);
        $ticket6->labels()->attach([$nietVoorOnsLabel->id, $onderzoekLabel->id]);

        // TICKET 7: High impact bug, in behandeling
        $ticket7 = Ticket::create([
            'ticket_number' => '#0007',
            'subject' => 'Betalingen worden niet verwerkt',
            'description' => "Sinds gisteren worden online betalingen niet meer correct verwerkt. Klanten klagen dat ze dubbel betalen. Dit moet ASAP opgelost worden!",
            'status' => 'in_progress',
            'impact' => 'high',
            'customer_id' => $customer5->id, // Zelfde klant als ticket 6
            'assigned_to' => $baziel->id
        ]);
        $ticket7->labels()->attach([$bugLabel->id, $urgentLabel->id]);

        // TICKET 8: Feature request, nog niet toegewezen
        $ticket8 = Ticket::create([
            'ticket_number' => '#0008',
            'subject' => 'Dark mode toevoegen aan dashboard',
            'description' => "Het zou fijn zijn als er een dark mode komt. Ik werk vaak 's avonds en dan is het scherm erg fel.",
            'status' => 'new',
            'impact' => 'low',
            'customer_id' => $customer6->id,
            'assigned_to' => null
        ]);
        $ticket8->labels()->attach([$featureLabel->id]);

        // TICKET 9: Documentatie vraag, bijna klaar
        $ticket9 = Ticket::create([
            'ticket_number' => '#0009',
            'subject' => 'Waar vind ik de API documentatie?',
            'description' => "We willen een custom integratie bouwen. Kunnen jullie me de API docs doorsturen?",
            'status' => 'to_close',
            'impact' => 'low',
            'customer_id' => $customer1->id, // Zelfde klant als ticket 1
            'assigned_to' => $nel->id
        ]);
        $ticket9->labels()->attach([$documentatieLabel->id]);

        // TICKET 10: Complexe bug met onderzoek nodig
        $ticket10 = Ticket::create([
            'ticket_number' => '#0010',
            'subject' => 'Rapportage toont geen data voor laatste maand',
            'description' => "Wanneer ik een rapport genereer voor januari 2026, krijg ik geen resultaten. Voor december 2025 werkt het wel perfect. Kunnen jullie dit onderzoeken?",
            'status' => 'in_progress',
            'impact' => 'high',
            'customer_id' => $customer3->id, // Zelfde klant als ticket 2
            'assigned_to' => $kevin->id
        ]);
        $ticket10->labels()->attach([$bugLabel->id, $onderzoekLabel->id, $urgentLabel->id]);
    }
}