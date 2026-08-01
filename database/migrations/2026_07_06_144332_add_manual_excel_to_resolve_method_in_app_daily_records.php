<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE app_daily_records MODIFY resolve_method ENUM('direct','shift_match','wallet_date','date_fallback','unresolved','single_user_id','wallet_fallback','manual_excel') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE app_daily_records MODIFY resolve_method ENUM('direct','shift_match','wallet_date','date_fallback','unresolved','single_user_id','wallet_fallback') NULL");
    }
};
