<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('iqama_number', 20)->unique();    // رقم الإقامة - المفتاح الرئيسي للعمل
            $table->string('name_ar')->nullable();
            $table->string('name_en');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('city')->nullable();
            // نوع العقد
            $table->enum('contract_type', ['salary', 'commission', 'both'])->default('salary');
            // نظام الراتب: salary = راتب ثابت مشروط، commission = شرائح (8 ريال)، hybrid = مزيج
            $table->enum('salary_system', ['fixed', 'commission_tiered', 'hybrid'])->default('fixed');
            $table->decimal('agreed_salary', 10, 2)->default(0); // الراتب المتفق عليه
            $table->foreignId('platform_id')->nullable()->constrained()->nullOnDelete(); // التطبيق الحالي (للدلالة فقط)
            $table->enum('employee_status', ['active', 'inactive', 'suspended', 'resigned'])->default('active');
            $table->date('hire_date')->nullable();
            $table->string('phone')->nullable();
            $table->string('nationality')->nullable();
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('iqama_number');
            $table->index(['branch_id', 'employee_status']);
        });

        // IDs التطبيق لكل سائق (سائق واحد قد يستخدم عدة IDs)
        Schema::create('employee_platform_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('captain_id');            // رقم ID في التطبيق
            $table->string('id_name')->nullable();       // اسم ID في التطبيق
            $table->date('start_date');
            $table->date('end_date')->nullable();        // NULL = لا يزال فعالاً
            $table->string('city')->nullable();
            $table->timestamps();

            // فهرس مركب للبحث السريع: captain_id + تاريخ
            $table->index(['captain_id', 'start_date', 'end_date']);
            $table->index(['platform_id', 'captain_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_platform_ids');
        Schema::dropIfExists('employees');
    }
};
