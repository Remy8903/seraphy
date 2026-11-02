<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clinical_log_forms', function (Blueprint $table) {
            $table->string('mode')->default('log_form')->after('user_id');
            $table->unsignedInteger('simple_minutes')->nullable()->after('hours_type');
            $table->json('days')->nullable()->change();
            $table->string('placement')->nullable()->change();
            $table->string('clinic_mode')->nullable()->change();
            $table->string('placement_name')->nullable()->change();
            $table->string('supervisor_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('clinical_log_forms')->where('mode', 'simple')->delete();

        Schema::table('clinical_log_forms', function (Blueprint $table) {
            $table->dropColumn(['mode', 'simple_minutes']);
            $table->json('days')->nullable(false)->change();
            $table->string('placement')->nullable(false)->change();
            $table->string('clinic_mode')->nullable(false)->change();
            $table->string('placement_name')->nullable(false)->change();
            $table->string('supervisor_name')->nullable(false)->change();
        });
    }
};
