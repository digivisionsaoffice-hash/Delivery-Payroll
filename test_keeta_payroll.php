<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$srv = new \App\Services\PayrollCalculationService();
$period = \App\Models\PayrollPeriod::where('platform_id', 2)->latest()->first();
if (!$period) {
    die("No period found for platform 2.\n");
}
$settings = \App\Models\PlatformSettings::where('platform_id', 2)->first();

$employeeId = \App\Models\AppDailyRecord::where('platform_id', 2)
                ->whereNotNull('employee_id')
                ->value('employee_id');
                
if (!$employeeId) {
    die("No employee found with Keeta records.\n");
}

$employee = \App\Models\Employee::find($employeeId);
$entry = $srv->calculateEntry($employee, $period, $settings);

print_r($entry);
