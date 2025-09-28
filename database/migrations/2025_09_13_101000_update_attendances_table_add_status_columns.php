<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('attended_at')->nullable()->change();
            $table->string('status')->default('attended')->after('attended_at');
            $table->string('source')->default('manual')->after('status');
            $table->timestamp('restored_at')->nullable()->after('marked_by');
            $table->foreignId('restored_by')->nullable()->after('restored_at')->constrained('users')->nullOnDelete();
            $table->text('restored_reason')->nullable()->after('restored_by');
        });

        DB::table('attendances')
            ->whereNull('status')
            ->update(['status' => 'attended']);

        DB::table('attendances')
            ->whereNull('source')
            ->update(['source' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restored_by');
            $table->dropColumn(['status', 'source', 'restored_at', 'restored_reason']);
            $table->timestamp('attended_at')->nullable(false)->change();
        });
    }
};
