<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // السلف النقدية
        Schema::create('advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payroll_month');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'payroll_month']);
        });

        // المخالفات المرورية
        Schema::create('traffic_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payroll_month');
            $table->string('violation_number')->nullable();
            $table->string('violation_type')->nullable();
            $table->date('violation_date')->nullable();
            $table->string('city')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('plate_number')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'payroll_month']);
        });

        // قطع الغيار - سوء استخدام
        Schema::create('spare_parts_misuse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payroll_month');
            $table->decimal('cost', 10, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('total_value', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'payroll_month']);
        });

        // الصيانة اليدوية - سوء استخدام
        Schema::create('manual_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payroll_month');
            $table->string('plate_number')->nullable();
            $table->text('spare_parts')->nullable();
            $table->text('reason')->nullable();
            $table->text('comments')->nullable();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
            $table->index(['employee_id', 'payroll_month']);
        });

        // جزاءات الشركة
        Schema::create('company_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payroll_month');
            $table->string('violation_title');
            $table->decimal('discount_amount', 10, 2);
            $table->date('penalty_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'payroll_month']);
        });

        // المدد (رواتب مُسبقة قبل المسير)
        Schema::create('pre_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('payroll_month');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'payroll_month']);
        });

        // المصاريف الشهرية على المندوب (للعمولة/8ريال فقط)
        Schema::create('employee_monthly_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('payroll_month');
            $table->decimal('fuel', 10, 2)->default(0);                     // البنزين
            $table->decimal('housing', 10, 2)->default(0);                  // السكن
            $table->decimal('packages', 10, 2)->default(0);                 // الباقات (نت)
            $table->decimal('consumable_maintenance', 10, 2)->default(0);   // صيانة استهلاكية
            $table->decimal('consumable_parts', 10, 2)->default(0);         // قطع غيار استهلاكية
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'payroll_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_monthly_expenses');
        Schema::dropIfExists('pre_salary_payments');
        Schema::dropIfExists('company_penalties');
        Schema::dropIfExists('manual_maintenance');
        Schema::dropIfExists('spare_parts_misuse');
        Schema::dropIfExists('traffic_violations');
        Schema::dropIfExists('advances');
    }
};
