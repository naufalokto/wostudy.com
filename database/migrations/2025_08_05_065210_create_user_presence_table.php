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
        Schema::create('user_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('todo_list_id')->constrained()->onDelete('cascade');
            $table->string('session_id');
            $table->enum('status', ['online', 'away', 'offline'])->default('online');
            $table->timestamp('last_activity_at');
            $table->json('current_activity')->nullable(); // What user is currently doing
            $table->string('cursor_position')->nullable(); // For real-time cursor tracking
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['todo_list_id', 'status']);
            $table->index(['user_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_presence');
    }
}; 