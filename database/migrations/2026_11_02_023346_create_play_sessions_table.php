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
        Schema::create('play_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_sessions');
    }
};
