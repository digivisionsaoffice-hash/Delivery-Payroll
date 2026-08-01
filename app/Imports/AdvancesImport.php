<?php
namespace App\Imports;
use App\Models\Advance;
class AdvancesImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        Advance::updateOrCreate(
            ['employee_id' => $empId, 'payroll_month' => $this->payrollMonth()],
            ['import_batch_id' => $this->batch->id, 'amount' => (float)($row['amount'] ?? 0), 'notes' => $row['notes'] ?? null]
        );
    }
}

