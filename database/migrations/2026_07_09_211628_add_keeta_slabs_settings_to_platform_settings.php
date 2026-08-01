<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('calc_mode', 50)->default('ninja')->after('link_target_to_hours');
            $table->json('keeta_slabs_config')->nullable()->after('calc_mode');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['calc_mode', 'keeta_slabs_config']);
        });
    }
};