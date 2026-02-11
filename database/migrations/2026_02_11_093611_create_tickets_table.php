<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_number')->unique();

            $table->string('subject');
            $table->text('description');

            $table->enum('status', [
                'new',
                'in_progress',
                'on_hold',
                'to_close',
                'closed'
            ])->default('new');

            // Relatie naar customer
            $table->foreignId('customer_id')
                ->constrained()
                ->onDelete('cascade');

            // Relatie naar user (agent)
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
