<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->boolean('is_active')->default(true);
// расписание
            $table->enum('schedule_type', ['weekly','monthly'])->default('weekly');
            $table->json('weekdays')->nullable(); // [1..7] пн=1
            $table->json('month_days')->nullable(); // [1..31]
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sections'); }
};
