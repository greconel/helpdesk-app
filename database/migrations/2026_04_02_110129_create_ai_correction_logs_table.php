<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_correction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Wat AI voorstelde
            $table->string('ai_impact')->nullable();
            $table->json('ai_labels')->nullable();
            $table->string('ai_skill_version')->nullable();

            // Wat agent deed
            $table->string('agent_impact')->nullable();
            $table->json('agent_labels')->nullable();

            // Context ticket
            $table->string('ticket_subject');
            $table->text('ticket_description_snippet');

            // Type correctie
            $table->enum('correction_type', ['impact_only', 'labels_only', 'both']);

            // Verwerkt door skill update command?
            $table->boolean('processed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_correction_logs');
    }
};