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
        Schema::create('activity_directions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('text', 150);
            $table->json('item_ids');
            $table->unsignedSmallInteger('position');
        });

        Schema::table('trials', function (Blueprint $table) {
            $table->foreignId('activity_direction_id')->nullable()->after('group_number')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_direction_id');
        });

        Schema::dropIfExists('activity_directions');
    }
};
