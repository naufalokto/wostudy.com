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
        Schema::create('collaborative_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_todo_list_id')->constrained()->onDelete('cascade');
            $table->enum('permission_type', ['can_edit', 'can_view']);
            $table->enum('status', ['online', 'offline', 'away'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->string('session_id')->nullable(); // Untuk tracking real-time session
            $table->json('user_agent')->nullable(); // Browser info
            $table->string('ip_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint untuk user dan todo list
            $table->unique(['todo_list_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaborative_participants');
    }
}; 