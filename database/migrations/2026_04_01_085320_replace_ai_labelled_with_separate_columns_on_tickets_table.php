<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('ai_labelled');
            $table->boolean('ai_labelled_impact')->default(false)->after('impact');
            $table->boolean('ai_labelled_labels')->default(false)->after('ai_labelled_impact');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['ai_labelled_impact', 'ai_labelled_labels']);
            $table->boolean('ai_labelled')->default(false);
        });
    }
};