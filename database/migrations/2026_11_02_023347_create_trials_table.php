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
        Schema::create('trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_item_id')->constrained()->restrictOnDelete();
            $table->string('result');
            $table->unsignedInteger('response_ms')->nullable();
            $table->uuid('client_uuid');
            $table->timestamps();

            $table->unique(['play_session_id', 'client_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trials');
    }
};
