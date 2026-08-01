<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\EmployeePlatformId::updateOrCreate(
    ['employee_id' => 1923, 'platform_id' => 1, 'captain_id' => '190198', 'start_date' => '2026-06-10'],
    ['end_date' => '2026-06-30', 'id_name' => 'Adeel Ali Hashmat ali']
);

$rs = new \App\Services\IqamaResolutionService();
$rs->resolveSingleUserIds(142, 1, '2026-06');
$rs->resolveRevenuesByDirectMatch(142, 1);
$rs->resolveSettlements(142, 1);
echo "Unresolved remaining: " . \App\Models\AppDailyRecord::where('platform_id', 1)->where('import_batch_id', 142)->whereNull('employee_id')->count() . "\n";
