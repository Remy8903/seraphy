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
        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('language', 2);
            $table->string('word', 100);
            $table->string('emoji', 16)->nullable();
            $table->string('image_path')->nullable();
            $table->string('target_sound', 10)->nullable();
            $table->string('word_position')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('pair_key', 50)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_items');
    }
};
