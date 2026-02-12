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
        // 1. Users aanmaken
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

        // 2. Labels aanmaken (NIEUW - alleen deze 4)
        $labels = [];
        $labelNamen = ['bug', 'onderzoek', 'feature request', 'eigenlijk niet voor ons'];
        foreach ($labelNamen as $naam) {
            $labels[] = Label::create(['name' => $naam]);
        }

        // 3. Customers aanmaken
        $c1 = Customer::create(['name' => 'Jan Janssen', 'email' => 'jan@mail.com']);
        $c2 = Customer::create(['name' => 'Piet Puk', 'email' => 'piet@mail.com']);
        $c3 = Customer::create(['name' => 'An de Vries', 'email' => 'an@mail.com']);

        // 4. Tickets aanmaken met IMPACT (enum) en LABELS (many-to-many)
        $t1 = Ticket::create([
            'ticket_number' => '#0001',
            'subject' => 'Inloggen werkt niet',
            'description' => 'Ik kan niet inloggen op het portaal.',
            'status' => 'new',
            'impact' => 'high',    // NIEUW: impact als enum
            'customer_id' => $c1->id
        ]);
        // Koppel labels (bug)
        $t1->labels()->attach([$labels[0]->id]); // bug

        $t2 = Ticket::create([
            'ticket_number' => '#0002',
            'subject' => 'Vraag over factuur',
            'description' => 'Mijn factuur klopt niet.',
            'status' => 'on_hold',
            'impact' => 'medium',  // NIEUW
            'customer_id' => $c2->id,
            'assigned_to' => $nel->id
        ]);
        // Koppel labels (onderzoek)
        $t2->labels()->attach([$labels[1]->id]); // onderzoek

        $t3 = Ticket::create([
            'ticket_number' => '#0003',
            'subject' => 'Feature verzoek: Dark mode',
            'description' => 'Kunnen jullie een dark mode toevoegen?',
            'status' => 'new',
            'impact' => 'low',     // NIEUW
            'customer_id' => $c3->id
        ]);
        // Koppel labels (feature request)
        $t3->labels()->attach([$labels[2]->id]); // feature request

        $t4 = Ticket::create([
            'ticket_number' => '#0004',
            'subject' => 'Probleem met extern systeem',
            'description' => 'Het externe systeem reageert niet.',
            'status' => 'in_progress',
            'impact' => 'medium',  // NIEUW
            'customer_id' => $c1->id,
            'assigned_to' => $baziel->id
        ]);
        // Koppel labels (eigenlijk niet voor ons)
        $t4->labels()->attach([$labels[3]->id]); // eigenlijk niet voor ons

        $t5 = Ticket::create([
            'ticket_number' => '#0005',
            'subject' => 'Bug in rapportage module',
            'description' => 'Rapportages tonen verkeerde data.',
            'status' => 'to_close',
            'impact' => 'high',    // NIEUW
            'customer_id' => $c2->id,
            'assigned_to' => $nel->id
        ]);
        // Koppel meerdere labels (bug + onderzoek)
        $t5->labels()->attach([$labels[0]->id, $labels[1]->id]); // bug + onderzoek
    }
}