<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rs = new \App\Services\IqamaResolutionService();
$rs->expandIdRanges(1, '2026-06');
$rs->resolveSingleUserIds(142, 1, '2026-06');
$rs->resolveRevenuesByDirectMatch(142, 1);
$rs->resolveSettlements(142, 1);
echo "Unresolved remaining: " . \App\Models\AppDailyRecord::where('platform_id', 1)->where('import_batch_id', 142)->whereNull('employee_id')->count() . "\n";
