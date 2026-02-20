<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->string('email_token')->nullable()->after('closed_at');
    });

    // Geef elk bestaand ticket een uniek token
    DB::table('tickets')->orderBy('id')->each(function ($ticket) {
    DB::table('tickets')
        ->where('id', $ticket->id)
        ->update(['email_token' => \Illuminate\Support\Str::uuid()]);
});

    Schema::table('tickets', function (Blueprint $table) {
        $table->string('email_token')->nullable(false)->unique()->change();
    });
}

public function down(): void
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->dropColumn('email_token');
    });
}
};
