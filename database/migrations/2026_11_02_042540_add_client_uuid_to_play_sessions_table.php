<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sessions played offline get a UUID on the device, so syncing them twice never adds a duplicate.
     */
    public function up(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });
    }
};
