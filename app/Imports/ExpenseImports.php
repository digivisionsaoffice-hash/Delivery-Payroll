<?php
namespace App\Imports;
use App\Models\EmployeeMonthlyExpense;
class FuelImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        EmployeeMonthlyExpense::updateOrCreate(['employee_id'=>$empId,'payroll_month'=>$this->payrollMonth()],['fuel'=>(float)($row['amount']??0),'import_batch_id'=>$this->batch->id]);
    }
}

class HousingImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        EmployeeMonthlyExpense::updateOrCreate(['employee_id'=>$empId,'payroll_month'=>$this->payrollMonth()],['housing'=>(float)($row['amount']??0),'import_batch_id'=>$this->batch->id]);
    }
}
class PackagesImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        EmployeeMonthlyExpense::updateOrCreate(['employee_id'=>$empId,'payroll_month'=>$this->payrollMonth()],['packages'=>(float)($row['amount']??0),'import_batch_id'=>$this->batch->id]);
    }
}
