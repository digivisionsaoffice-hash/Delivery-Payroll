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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE app_daily_records MODIFY resolve_method ENUM('direct','shift_match','wallet_date','date_fallback','unresolved')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE app_daily_records MODIFY resolve_method ENUM('direct','shift_match','date_fallback','unresolved')");
    }
};
