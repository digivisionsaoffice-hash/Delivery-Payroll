<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // نينجا، هنقرستيشن، ...
            $table->string('name_en')->nullable();
            $table->string('logo')->nullable();
            $table->enum('billing_type', ['per_order', 'per_shift', 'tiered', 'mixed'])->default('per_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->date('month');                           // شهر الضبط (أول الشهر)
            $table->string('app_name')->nullable();          // اسم التطبيق في الضبط
            $table->integer('daily_target')->default(0);     // تارجت الحافز اليومي
            $table->decimal('bonus_per_excess_order', 8, 2)->default(0); // قيمة الحافز/طلب زائد
            $table->decimal('min_working_hours_per_day', 4, 1)->default(10); // ساعات الدوام
            $table->integer('monthly_target')->default(0);   // التارجت الشهري (450 مثلاً)
            // شرائح العمولة - JSON للمرونة
            // مثال: [{"from":0,"to":200,"rate":3},{"from":200,"to":450,"rate":4},{"from":450,"to":null,"rate":8}]
            $table->json('commission_tiers')->nullable();
            $table->unique(['platform_id', 'month']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('platforms');
    }
};
