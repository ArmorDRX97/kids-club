<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->string('name'); // например: "12 занятий", "Месячный абонемент"
            $table->enum('billing_type', ['visits','period']); // по занятиям / по времени
            $table->unsignedInteger('visits_count')->nullable(); // для visits
            $table->unsignedInteger('days')->nullable();         // для period
            $table->unsignedInteger('price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('packages'); }
};
