<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('label_ticket', function (Blueprint $table) {
            $table->boolean('ai_labelled')->default(false)->after('label_id');
        });
    }

    public function down(): void
    {
        Schema::table('label_ticket', function (Blueprint $table) {
            $table->dropColumn('ai_labelled');
        });
    }
};