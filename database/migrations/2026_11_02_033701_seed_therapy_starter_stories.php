<?php

use Database\Seeders\TherapyStarterContentSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add the starter story cards to the shared library.
     */
    public function up(): void
    {
        (new TherapyStarterContentSeeder)->seedStories();
    }

    /**
     * Remove the starter story cards that are not used by any activity.
     */
    public function down(): void
    {
        DB::table('content_items')
            ->whereNull('user_id')
            ->whereNotNull('story_key')
            ->whereNotIn('id', DB::table('activity_items')->select('content_item_id'))
            ->delete();
    }
};
