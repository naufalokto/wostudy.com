<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('todo_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Contoh: "Tugas Individu", "Tugas Kelompok", "Quiz", "Ujian"
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#007bff'); // Hex color code
            $table->string('icon', 50)->nullable(); // Icon class atau nama
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_categories');
    }
};
