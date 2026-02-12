<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Verwijder eerst de oude impact kolom als die bestaat
            if (Schema::hasColumn('tickets', 'impact')) {
                $table->dropColumn('impact');
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            // Voeg impact toe als nullable (optioneel)
            $table->enum('impact', ['low', 'medium', 'high'])
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('impact');
        });
    }
};