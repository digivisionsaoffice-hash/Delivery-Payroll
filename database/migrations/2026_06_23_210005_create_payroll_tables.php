<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // دورات الرواتب الشهرية
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->date('month');                          // أول الشهر
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->unique(['platform_id', 'month']);
            $table->timestamps();
        });

        // نتيجة راتب كل موظف (المسير النهائي)
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('iqama_number', 20);

            // بيانات الأداء
            $table->integer('total_orders')->default(0);
            $table->integer('working_days')->default(0);
            $table->integer('daily_target_excess')->default(0);  // الطلبات الزائدة عن التارجت اليومي المجمعة
            $table->decimal('total_revenue', 12, 2)->default(0); // مجموع suppliers_costs

            // مكونات الراتب
            $table->decimal('agreed_salary', 10, 2)->default(0);
            $table->decimal('basic_salary', 10, 2)->default(0);   // الراتب الأساسي المحسوب
            $table->decimal('bonus', 10, 2)->default(0);          // البونص (فوق التارجت اليومي)
            $table->decimal('total_salary', 10, 2)->default(0);   // basic_salary + bonus

            // الخصومات
            $table->decimal('app_settlements', 10, 2)->default(0);    // تسويات التطبيق (سالبة)
            $table->decimal('advances', 10, 2)->default(0);           // السلف النقدية
            $table->decimal('traffic_violations', 10, 2)->default(0); // المخالفات المرورية
            $table->decimal('spare_parts', 10, 2)->default(0);        // قطع الغيار سوء استخدام
            $table->decimal('maintenance', 10, 2)->default(0);        // الصيانة اليدوية
            $table->decimal('company_discount', 10, 2)->default(0);   // جزاءات الشركة
            // خصومات إضافية للعمولة (نظام 8 ريال)
            $table->decimal('fuel', 10, 2)->default(0);
            $table->decimal('housing', 10, 2)->default(0);
            $table->decimal('packages', 10, 2)->default(0);
            $table->decimal('consumable_maintenance', 10, 2)->default(0);
            $table->decimal('consumable_parts', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);

            // النتائج
            $table->decimal('net_salary', 10, 2)->default(0);        // total_salary - total_deductions
            $table->decimal('pre_salary_paid', 10, 2)->default(0);   // المدد المصروفة مسبقاً
            $table->decimal('remaining_salary', 10, 2)->default(0);  // net_salary - pre_salary_paid

            // الربحية
            $table->decimal('total_driver_cost', 12, 2)->default(0); // الراتب + كل المصاريف المباشرة
            $table->decimal('profit_loss', 12, 2)->default(0);       // total_revenue - total_driver_cost

            // بيانات مرجعية
            $table->string('contract_type')->nullable();
            $table->string('salary_system')->nullable();
            $table->string('application_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('city')->nullable();
            $table->text('id_numbers')->nullable();  // الـ IDs التي عمل بها مفصولة بفاصلة

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['payroll_period_id', 'profit_loss']); // للترتيب بالربحية
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_periods');
    }
};
