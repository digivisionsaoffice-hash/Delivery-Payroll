<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$iqamas = ['2626616896', '2612947032'];
foreach ($iqamas as $iqama) {
    echo "====================================\n";
    $emp = \App\Models\Employee::where('iqama_number', $iqama)->first();
    echo "Employee ID: {$emp->id} - {$emp->name_ar}\n";
    $exp = \App\Models\EmployeeMonthlyExpense::where('employee_id', $emp->id)->count();
    echo "Expenses (Fuel/Housing/etc): $exp\n";
}
