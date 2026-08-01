<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_monthly_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('employee_monthly_expenses', 'consumable_parts')) {
                $table->dropColumn('consumable_parts');
            }
            if (Schema::hasColumn('employee_monthly_expenses', 'spare_parts')) {
                $table->dropColumn('spare_parts');
            }
            if (Schema::hasColumn('employee_monthly_expenses', 'labor_charges')) {
                $table->dropColumn('labor_charges');
            }
        });

        Schema::table('payroll_entries', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_entries', 'consumable_parts')) {
                $table->dropColumn('consumable_parts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_monthly_expenses', function (Blueprint $table) {
            $table->decimal('consumable_parts', 10, 2)->default(0);
        });

        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->decimal('consumable_parts', 10, 2)->default(0);
        });
    }
};
