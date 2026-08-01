<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // سجل استيراد الملفات
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->date('month');                       // الشهر المعني
            $table->enum('sheet_type', [
                'app_report',        // تقرير التطبيق النهائي
                'id_changes',        // تغيير الـ IDs
                'advances',          // السلف النقدية
                'violations',        // المخالفات المرورية
                'spare_parts',       // قطع الغيار (سوء استخدام)
                'maintenance',       // الصيانة اليدوية
                'penalties',         // جزاءات الشركة
                'pre_salary',        // مدد (رواتب مُسبقة)
                'fuel',              // البنزين
                'consumable_parts',  // قطع الغيار الاستهلاكية
                'housing',           // السكن
                'packages',          // الباقات (نت، شرائح)
            ]);
            $table->string('file_name');
            $table->integer('rows_imported')->default(0);
            $table->integer('rows_failed')->default(0);
            $table->json('errors')->nullable();
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['platform_id', 'month', 'sheet_type']);
        });

        // تقرير التطبيق اليومي (مخرج المعالجة)
        Schema::create('app_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->bigInteger('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('contract_type')->nullable();
            $table->bigInteger('captain_id')->nullable();
            $table->bigInteger('shift_id')->nullable();
            $table->string('captain_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->text('wallet_note')->nullable();
            $table->decimal('working_hours', 6, 2)->default(0);
            $table->decimal('dynamic_per_hour', 8, 2)->default(0);
            $table->integer('orders')->default(0);
            $table->decimal('suppliers_costs', 10, 2)->default(0);    // إيراد السائق
            $table->decimal('bonus_ftr', 10, 2)->default(0);
            $table->decimal('adjustments', 10, 2)->default(0);        // التسويات +-
            $table->decimal('net_cost', 10, 2)->default(0);
            $table->decimal('vat_15', 10, 2)->default(0);
            $table->decimal('total_dues', 10, 2)->default(0);
            // نتيجة ربط الإقامة (المعالجة 2 و 3)
            $table->string('resolved_iqama', 20)->nullable();
            $table->enum('resolve_method', [
                'direct',         // تطابق مباشر captain_id + date
                'shift_match',    // تطابق عبر shift_id
                'date_fallback',  // تراجع تاريخي
                'unresolved',     // لم يُحل
            ])->nullable();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_settlement')->default(false); // هل هذا السطر تسوية؟

            $table->index(['platform_id', 'record_date']);
            $table->index(['captain_id', 'record_date']);
            $table->index('resolved_iqama');
            $table->index(['employee_id', 'record_date']);
            $table->timestamps();
        });

        // سجل الـ IDs اليومي (مخرج المعالجة 1 — توسيع نطاق التواريخ)
        Schema::create('employee_id_daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('iqama_number', 20);
            $table->bigInteger('captain_id');
            $table->date('work_date');           // تاريخ اليوم (بعد التوسيع)
            $table->date('month');               // الشهر المستورد

            $table->index(['captain_id', 'work_date']);
            $table->index(['iqama_number', 'work_date']);
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_id_daily_records');
        Schema::dropIfExists('app_daily_records');
        Schema::dropIfExists('import_batches');
    }
};
