<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->timestamp('scheduled_start_at')->nullable()->after('started_at');
            $table->timestamp('scheduled_end_at')->nullable()->after('scheduled_start_at');
            $table->boolean('auto_close_enabled')->default(true)->after('scheduled_end_at');
            $table->boolean('closed_automatically')->default(false)->after('auto_close_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['scheduled_start_at', 'scheduled_end_at', 'auto_close_enabled', 'closed_automatically']);
        });
    }
};
