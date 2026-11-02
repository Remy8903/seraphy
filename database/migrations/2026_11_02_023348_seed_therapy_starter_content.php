<?php

use Database\Seeders\TherapyStarterContentSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Load the starter word library so every environment has it without a separate seed step.
     */
    public function up(): void
    {
        (new TherapyStarterContentSeeder)->seedWords();
    }

    /**
     * Remove the starter word library.
     */
    public function down(): void
    {
        DB::table('content_items')->whereNull('user_id')->delete();
    }
};
