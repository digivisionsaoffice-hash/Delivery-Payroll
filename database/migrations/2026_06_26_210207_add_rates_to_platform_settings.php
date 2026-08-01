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
            $table->decimal('absence_deduction_rate', 4, 2)->default(1.0)->after('target_working_days')->comment('مضاعف خصم الغياب (يوم بيوم = 1، يوم بيوم ونصف = 1.5)');
            $table->decimal('extra_day_bonus_rate', 4, 2)->default(1.0)->after('absence_deduction_rate')->comment('مضاعف بونص العمل الإضافي');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['absence_deduction_rate', 'extra_day_bonus_rate']);
        });
    }
};
