<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('section_schedule_id')->nullable()->constrained('section_schedules')->nullOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->date('started_at');
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('visits_left')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'expired'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
