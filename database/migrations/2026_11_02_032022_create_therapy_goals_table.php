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
        Schema::create('therapy_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('game_type')->nullable();
            $table->string('language', 2)->nullable();
            $table->string('target_sound', 10)->nullable();
            $table->string('word_position')->nullable();
            $table->unsignedTinyInteger('target_accuracy');
            $table->unsignedTinyInteger('consecutive_sessions');
            $table->unsignedTinyInteger('min_trials_per_session')->default(10);
            $table->string('status');
            $table->timestamp('achieved_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('therapy_goals');
    }
};
