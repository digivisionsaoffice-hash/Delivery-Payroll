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
        Schema::table('employee_platform_ids', function (Blueprint $table) {
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_platform_ids', function (Blueprint $table) {
            $table->dropForeign(['import_batch_id']);
            $table->dropColumn('import_batch_id');
        });
    }
};
