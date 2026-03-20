<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('motion_user_id')->nullable()->after('role');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('motion_task_id')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('motion_user_id');
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('motion_task_id');
        });
    }
};
