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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('todo_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path', 500);
            $table->bigInteger('file_size')->unsigned();
            $table->string('mime_type', 100);
            $table->string('file_extension', 20);
            $table->text('description')->nullable();
            $table->integer('download_count')->default(0);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
