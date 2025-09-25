<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('patronymic')->nullable(); // отчество
            $table->date('dob')->nullable();
            $table->string('child_phone', 40)->nullable(); // телефон ребёнка (если есть)
            $table->string('parent_phone', 40)->nullable();
            $table->string('parent2_phone', 40)->nullable();
            $table->boolean('is_active')->default(true); // вместо удаления
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['last_name','first_name']);
            $table->index('is_active');
        });
    }
    public function down(): void { Schema::dropIfExists('children'); }
};
