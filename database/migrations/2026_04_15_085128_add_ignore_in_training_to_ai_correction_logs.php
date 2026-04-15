<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_correction_logs', function (Blueprint $table) {
            $table->boolean('ignore_in_training')
                ->default(false)
                ->after('processed');
            $table->text('ignore_reason')
                ->nullable()
                ->after('ignore_in_training');
        });
    }

    public function down(): void
    {
        Schema::table('ai_correction_logs', function (Blueprint $table) {
            $table->dropColumn(['ignore_in_training', 'ignore_reason']);
        });
    }
};