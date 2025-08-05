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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->string('course_code', 20); // Contoh: "CS101"
            $table->string('course_name');
            $table->enum('course_type', ['LAB', 'LEC']);
            $table->tinyInteger('credits')->default(3);
            $table->text('description')->nullable();
            $table->string('schedule_day', 20)->nullable(); // Senin, Selasa, dst
            $table->string('schedule_time', 50)->nullable(); // 08:00-10:30
            $table->string('room', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint untuk course_code dalam satu semester
            $table->unique(['course_code', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
