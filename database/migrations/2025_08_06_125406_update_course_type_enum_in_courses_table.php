<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add a temporary column
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('course_type_new', ['lab_only', 'lecture', 'lab', 'lecture_only'])->after('course_type');
        });
        
        // Copy data with mapping
        DB::statement("UPDATE courses SET course_type_new = 'lab' WHERE course_type = 'LAB'");
        DB::statement("UPDATE courses SET course_type_new = 'lecture' WHERE course_type = 'LEC'");
        
        // Drop the old column and rename the new one
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('course_type');
        });
        
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('course_type_new', 'course_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, add a temporary column
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('course_type_old', ['LAB', 'LEC'])->after('course_type');
        });
        
        // Copy data with mapping
        DB::statement("UPDATE courses SET course_type_old = 'LAB' WHERE course_type IN ('lab', 'lab_only')");
        DB::statement("UPDATE courses SET course_type_old = 'LEC' WHERE course_type IN ('lecture', 'lecture_only')");
        
        // Drop the new column and rename the old one
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('course_type');
        });
        
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('course_type_old', 'course_type');
        });
    }
};
