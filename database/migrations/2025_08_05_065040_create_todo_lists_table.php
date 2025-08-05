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
        Schema::create('todo_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null'); // NULL untuk todo personal
            $table->foreignId('category_id')->constrained('todo_categories')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('task_type', ['individual', 'group'])->default('individual');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('deadline')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_public')->default(false); // Untuk collaborative dashboard
            $table->string('share_token')->unique()->nullable(); // Token untuk sharing
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todo_lists');
    }
};
