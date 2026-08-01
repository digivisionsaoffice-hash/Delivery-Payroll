<?php
namespace App\Imports;
use App\Models\SparePartsMisuse;
class SparePartsImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        $cost = (float)($row['cost']??0); $qty = (int)($row['quantity']??1);
        SparePartsMisuse::updateOrCreate(
            ['employee_id' => $empId, 'payroll_month' => $this->payrollMonth()],
            ['import_batch_id'=>$this->batch->id,'cost'=>$cost,'quantity'=>$qty,'total_value'=>(float)($row['value']??($cost*$qty)),'notes'=>$row['notes']??null]
        );
    }
}

