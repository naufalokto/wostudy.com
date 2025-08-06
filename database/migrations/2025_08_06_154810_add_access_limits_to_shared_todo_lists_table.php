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
        Schema::table('shared_todo_lists', function (Blueprint $table) {
            $table->integer('max_concurrent_users')->default(10)->after('expires_at');
            $table->integer('max_daily_access')->default(100)->after('max_concurrent_users');
            $table->integer('max_session_duration')->default(3600)->after('max_daily_access'); // dalam detik
            $table->json('allowed_countries')->nullable()->after('max_session_duration');
            $table->boolean('require_approval')->default(false)->after('allowed_countries');
            $table->string('access_password')->nullable()->after('require_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shared_todo_lists', function (Blueprint $table) {
            $table->dropColumn([
                'max_concurrent_users',
                'max_daily_access', 
                'max_session_duration',
                'allowed_countries',
                'require_approval',
                'access_password'
            ]);
        });
    }
};
