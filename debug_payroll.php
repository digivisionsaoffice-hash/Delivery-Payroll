<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$period = \App\Models\PayrollPeriod::latest('id')->first();
echo "Period: {$period->id} - Month: {$period->month}\n";

$svc = app(\App\Services\PayrollCalculationService::class);

$employeeIds = \App\Models\AppDailyRecord::where('platform_id', $period->platform_id)
    ->whereBetween('record_date', [
        date('Y-m-01', strtotime($period->month)),
        date('Y-m-t', strtotime($period->month)),
    ])
    ->whereNotNull('employee_id')
    ->distinct()
    ->pluck('employee_id');

echo "Employees count: " . $employeeIds->count() . "\n";

$advancesSum = 0;
foreach ($employeeIds as $empId) {
    $deductions = $svc->sumAllDeductions($empId, $period->month->format('Y-m-01'), false);
    $advancesSum += $deductions['advances'];
}

echo "Total Advances from iteration: {$advancesSum}\n";

// Now run the actual full payroll
$svc->calculateFullPayroll($period);

echo "PayrollEntry sum advances: " . \App\Models\PayrollEntry::where('payroll_period_id', $period->id)->sum('advances') . "\n";
