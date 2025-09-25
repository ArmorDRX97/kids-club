<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // название
            $table->string('number_label')->nullable(); // произвольная нумерация
            $table->unsignedInteger('capacity')->nullable();
            $table->text('spec')->nullable(); // спецификация
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('rooms'); }
};
