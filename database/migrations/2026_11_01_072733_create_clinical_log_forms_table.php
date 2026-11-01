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
        Schema::create('clinical_log_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year');
            $table->unsignedTinyInteger('semester');
            $table->unsignedSmallInteger('session_start_year');
            $table->json('days');
            $table->string('placement');
            $table->string('clinic_mode');
            $table->string('placement_name');
            $table->string('supervisor_name');
            $table->string('practicum_type');
            $table->string('hours_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_log_forms');
    }
};
