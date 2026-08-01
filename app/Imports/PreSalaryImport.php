<?php
namespace App\Imports;
use App\Models\PreSalaryPayment;
class PreSalaryImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        $rawArr = is_array($row) ? $row : $row->toArray();
        $existing = PreSalaryPayment::where('employee_id', $empId)->where('payroll_month', $this->payrollMonth())->first();
        $amount = (float)$this->getValue($rawArr, ['amount', 'المبلغ', 'القيمة', 'amount ']);
        $notes = $this->getValue($rawArr, ['notes', 'ملاحظات', 'ملاحظة', 'notes ']);
        
        if ($existing) {
            if ((float)$existing->amount === $amount) {
                $this->unchangedCount++;
            } else {
                $existing->update(['amount' => $amount, 'import_batch_id' => $this->batch->id, 'notes' => $notes]);
                $this->updatedCount++;
            }
        } else {
            PreSalaryPayment::create([
                'employee_id' => $empId,
                'payroll_month' => $this->payrollMonth(),
                'import_batch_id' => $this->batch->id,
                'amount' => $amount,
                'notes' => $notes
            ]);
            $this->insertedCount++;
        }
    }
}

