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
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('type', ['pg', 'essay'])->default('pg')->after('subject_id');
        });
        
        DB::statement('ALTER TABLE questions MODIFY option_a TEXT NULL;');
        DB::statement('ALTER TABLE questions MODIFY option_b TEXT NULL;');
        DB::statement('ALTER TABLE questions MODIFY option_c TEXT NULL;');
        DB::statement('ALTER TABLE questions MODIFY option_d TEXT NULL;');
        DB::statement('ALTER TABLE questions MODIFY answer VARCHAR(255) NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
