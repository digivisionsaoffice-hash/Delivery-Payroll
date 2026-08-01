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
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('absence_deduction_type', 50)->default('worked_days_only')->after('target_working_days')->comment('نوع الخصم عند الغياب: worked_days_only أو standard_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('absence_deduction_type');
        });
    }
};
