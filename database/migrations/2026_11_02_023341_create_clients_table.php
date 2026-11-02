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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_code', 30);
            $table->string('age_group');
            $table->string('language', 2);
            $table->string('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reference_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
