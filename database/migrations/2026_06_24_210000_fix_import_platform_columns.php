<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. توسيع sheet_type من ENUM محدود إلى VARCHAR مرن
        //    هذا يتيح إضافة أنواع جديدة بدون migration مستقبلاً
        DB::statement("ALTER TABLE import_batches MODIFY COLUMN sheet_type VARCHAR(50) NOT NULL DEFAULT 'app_report'");

        // 2. تحويل captain_id من bigInteger إلى string
        //    لأن كيتا لديها أرقام captain طويلة جداً تتجاوز حد bigint
        Schema::table('app_daily_records', function (Blueprint $table) {
            $table->string('captain_id_text', 50)->nullable()->after('captain_id');
        });

        // نسخ البيانات الموجودة
        DB::statement("UPDATE app_daily_records SET captain_id_text = CAST(captain_id AS CHAR) WHERE captain_id IS NOT NULL");

        Schema::table('app_daily_records', function (Blueprint $table) {
            $table->dropIndex(['captain_id', 'record_date']);
            $table->dropColumn('captain_id');
        });

        Schema::table('app_daily_records', function (Blueprint $table) {
            $table->renameColumn('captain_id_text', 'captain_id');
        });

        // إعادة بناء الـ index بعد تغيير النوع
        Schema::table('app_daily_records', function (Blueprint $table) {
            $table->index(['captain_id', 'record_date']);
        });

        // 3. إضافة عمود report_format للمنصات لتحديد شكل القالب
        Schema::table('platforms', function (Blueprint $table) {
            $table->string('report_format', 30)->default('ninja')->after('billing_type');
            // القيم المتاحة: ninja | keeta_orders | keeta_slabs | hunger | jahez | generic
        });

        // 4. إضافة عمود unknown_columns لسجل الاستيراد (الأعمدة غير المعروفة)
        Schema::table('import_batches', function (Blueprint $table) {
            $table->json('unknown_columns')->nullable()->after('errors');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn('unknown_columns');
        });

        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('report_format');
        });

        DB::statement("ALTER TABLE import_batches MODIFY COLUMN sheet_type ENUM(
            'app_report','id_changes','advances','violations',
            'spare_parts','maintenance','penalties','pre_salary',
            'fuel','consumable_parts','housing','packages'
        ) NOT NULL");
    }
};
