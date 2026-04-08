<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => Ticket::generateTicketNumber(),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['new', 'in_progress', 'on_hold', 'to_close', 'closed']),
            'impact' => $this->faker->randomElement(['low', 'medium', 'high']),
            'customer_id' => Customer::factory(),
            'source' => 'email',
        ];
    }
}
