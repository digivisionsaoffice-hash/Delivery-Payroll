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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('app_id')->nullable()->after('platform_id');
            $table->string('vehicle_number')->nullable()->after('app_id');
            $table->decimal('discount_factor', 8, 2)->nullable()->after('vehicle_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['app_id', 'vehicle_number', 'discount_factor']);
        });
    }
};
