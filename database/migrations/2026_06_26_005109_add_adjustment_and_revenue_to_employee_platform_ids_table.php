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
        Schema::table('employee_platform_ids', function (Blueprint $table) {
            $table->decimal('adjustment_amount', 10, 2)->nullable()->after('end_date')->comment('التسويات');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_platform_ids', function (Blueprint $table) {
            $table->dropColumn(['adjustment_amount']);
        });
    }
};
