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
        Schema::table('app_daily_records', function (Blueprint $table) {
            $table->decimal('bonus_capacity', 8, 2)->default(0)->after('bonus_ftr');
            $table->decimal('bonus_trial', 8, 2)->default(0)->after('bonus_capacity');
            $table->decimal('food_damage', 8, 2)->default(0)->after('net_cost');
            $table->decimal('tga_discount', 8, 2)->default(0)->after('food_damage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_daily_records', function (Blueprint $table) {
            $table->dropColumn([
                'bonus_capacity',
                'bonus_trial',
                'food_damage',
                'tga_discount'
            ]);
        });
    }
};
