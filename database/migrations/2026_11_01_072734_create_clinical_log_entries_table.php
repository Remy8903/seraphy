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
        Schema::create('clinical_log_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_log_form_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('age_group');
            $table->string('rn_no', 50)->nullable();
            $table->string('session_mode');
            $table->string('session_type');
            $table->string('diagnosis')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinical_log_entries');
    }
};
