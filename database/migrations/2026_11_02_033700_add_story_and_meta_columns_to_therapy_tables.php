<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->string('story_key', 50)->nullable()->after('pair_key');
            $table->unsignedTinyInteger('story_position')->nullable()->after('story_key');
        });

        Schema::table('activity_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('group_number')->nullable()->after('paired_item_id');
        });

        Schema::table('trials', function (Blueprint $table) {
            $table->foreignId('content_item_id')->nullable()->change();
            $table->unsignedSmallInteger('group_number')->nullable()->after('content_item_id');
            $table->json('meta')->nullable()->after('response_ms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Story answers have no single word, so they are deleted before the word becomes required again.
     */
    public function down(): void
    {
        DB::table('trials')->whereNull('content_item_id')->delete();

        Schema::table('trials', function (Blueprint $table) {
            $table->dropColumn(['group_number', 'meta']);
            $table->foreignId('content_item_id')->nullable(false)->change();
        });

        Schema::table('activity_items', function (Blueprint $table) {
            $table->dropColumn('group_number');
        });

        Schema::table('content_items', function (Blueprint $table) {
            $table->dropColumn(['story_key', 'story_position']);
        });
    }
};
