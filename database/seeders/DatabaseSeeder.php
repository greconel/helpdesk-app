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
        // 1. Users aanmaken (Nel & Baziel)
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

        // 2. Labels aanmaken
        $labels = [];
        $namen = ['bug', 'niet voor ons', 'low impact', 'onderzoek', 'high impact'];
        foreach ($namen as $naam) {
            $labels[] = Label::create(['name' => $naam]);
        }

        // 3. Customers aanmaken
        $c1 = Customer::create(['name' => 'Jan Janssen', 'email' => 'jan@mail.com']);
        $c2 = Customer::create(['name' => 'Piet Puk', 'email' => 'piet@mail.com']);
        $c3 = Customer::create(['name' => 'An de Vries', 'email' => 'an@mail.com']);

        // 4. Tickets aanmaken (Handmatig gekoppeld)
        $t1 = Ticket::create([
            'ticket_number' => '#0001',
            'subject' => 'Inloggen werkt niet',
            'description' => 'Ik kan niet inloggen op het portaal.',
            'status' => 'new',
            'customer_id' => $c1->id
        ]);
        
        // Koppel labels aan ticket 1 (Many-to-Many)
        $t1->labels()->attach([$labels[0]->id, $labels[4]->id]); // bug + high impact

        Ticket::create([
            'ticket_number' => '#0002',
            'subject' => 'Vraag over factuur',
            'description' => 'Mijn factuur klopt niet.',
            'status' => 'on_hold',
            'customer_id' => $c2->id,
            'assigned_to' => $nel->id
        ]);

        // ... herhaal dit voor de overige 3 tickets ...
    }
}