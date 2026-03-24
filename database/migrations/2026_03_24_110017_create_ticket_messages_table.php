<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_ticket_messages_table.php
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // agent
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('message_id')->nullable()->unique(); // Microsoft Message-ID
            $table->string('in_reply_to')->nullable();         // voor threading
            $table->string('internet_message_id')->nullable(); // RFC 5322 Message-ID header
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('message_id');
            $table->index('in_reply_to');
            $table->index('internet_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
