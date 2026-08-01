<?php
namespace App\Imports;
use App\Models\ManualMaintenance;
class MaintenanceImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        $rawArr = is_array($row) ? $row : $row->toArray();
        $existing = ManualMaintenance::where('employee_id', $empId)
            ->where('payroll_month', $this->payrollMonth())
            ->first();

        $newAmount = (float)$this->getValue($rawArr, ['discount_amount', 'discount amount', 'مبلغ الخصم', 'الخصم', 'amount']);
        $newReason = $this->getValue($rawArr, ['the_reason', 'the reason', 'السبب', 'سبب']);
        $comments = $this->getValue($rawArr, ['comments', 'الملاحظات', 'ملاحظات', 'notes']);
        $plateNumber = $this->getValue($rawArr, ['plate_number', 'plate number', 'رقم اللوحة', 'اللوحة']);
        $spareParts = $this->getValue($rawArr, ['spare_parts', 'spare parts', 'قطع غيار', 'القطع']);

        if ($existing) {
            if ($existing->import_batch_id === $this->batch->id) {
                $existing->update([
                    'discount_amount' => $existing->discount_amount + $newAmount,
                    'reason' => $existing->reason . ($newReason ? ' | ' . $newReason : ''),
                ]);
                $this->updatedCount++;
            } else {
                if ((float)$existing->discount_amount === (float)$newAmount && $existing->reason === $newReason) {
                    $this->unchangedCount++;
                } else {
                    $existing->update([
                        'discount_amount' => $newAmount,
                        'reason' => $newReason,
                        'import_batch_id' => $this->batch->id,
                    ]);
                    $this->updatedCount++;
                }
            }
        } else {
            ManualMaintenance::create([
                'employee_id' => $empId,
                'payroll_month' => $this->payrollMonth(),
                'import_batch_id' => $this->batch->id,
                'plate_number' => $plateNumber,
                'spare_parts' => $spareParts,
                'reason' => $newReason,
                'comments' => $comments,
                'discount_amount' => $newAmount
            ]);
            $this->insertedCount++;
        }
    }
}
