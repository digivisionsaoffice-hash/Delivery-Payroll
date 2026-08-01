<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$iqamas = ['2626616896', '2612947032'];
$month = '2026-05-01';

foreach ($iqamas as $iqama) {
    echo "====================================\n";
    echo "Checking Iqama: $iqama\n";
    $emp = \App\Models\Employee::where('iqama_number', $iqama)->first();
    if (!$emp) {
        echo "Employee not found!\n";
        continue;
    }
    echo "Employee ID: {$emp->id} | Name: {$emp->name_ar}\n";
    
    // Check deductions
    $adv = \App\Models\Advance::where('employee_id', $emp->id)->where('payroll_month', $month)->sum('amount');
    $pen = \App\Models\CompanyPenalty::where('employee_id', $emp->id)->where('payroll_month', $month)->sum('discount_amount');
    $maint = \App\Models\ManualMaintenance::where('employee_id', $emp->id)->where('payroll_month', $month)->sum('discount_amount');
    
    echo "Deductions in DB -> Advances: $adv | Penalties: $pen | Maintenance: $maint\n";
    
    // Check Payroll Entry
    $entry = \App\Models\PayrollEntry::where('employee_id', $emp->id)->orderBy('id', 'desc')->first();
    if ($entry) {
        echo "Payroll Entry Found (Period {$entry->payroll_period_id})\n";
        echo "  - Advances: {$entry->advances}\n";
        echo "  - Penalties: {$entry->company_discount}\n";
        echo "  - Maintenance: {$entry->maintenance}\n";
    } else {
        echo "NO PAYROLL ENTRY FOUND FOR THIS EMPLOYEE!\n";
    }
}
echo "====================================\n";
