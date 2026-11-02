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
        Schema::create('activity_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('paired_item_id')->nullable()->constrained('content_items')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_items');
    }
};
