<?php
namespace App\Imports;
use App\Models\CompanyPenalty;
use Carbon\Carbon;
class PenaltiesImport extends BaseDeductionImport {
    protected function saveRow(int $empId, $row): void {
        $rawArr = is_array($row) ? $row : $row->toArray();
        $date = null;
        $v = $this->getValue($rawArr, ['date', 'التاريخ', 'تاريخ']);
        if (!empty($v)) { 
            $date = is_numeric($v) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d') : Carbon::parse($v)->format('Y-m-d'); 
        }
        
        $existing = CompanyPenalty::where('employee_id', $empId)
            ->where('payroll_month', $this->payrollMonth())
            ->first();

        $newAmount = (float)$this->getValue($rawArr, ['discount_amount', 'discount amount', 'مبلغ الخصم', 'الخصم', 'amount']);
        $newTitle = $this->getValue($rawArr, ['title_of_violation', 'violation_title', 'عنوان المخالفة', 'المخالفة', 'title']) ?? '';
        $notes = $this->getValue($rawArr, ['notes', 'الملاحظات', 'ملاحظات', 'comments']);

        if ($existing) {
            if ($existing->import_batch_id === $this->batch->id) {
                $existing->update([
                    'discount_amount' => $existing->discount_amount + $newAmount,
                    'violation_title' => $existing->violation_title . ($newTitle ? ' | ' . $newTitle : ''),
                ]);
                $this->updatedCount++;
            } else {
                if ((float)$existing->discount_amount === (float)$newAmount && $existing->violation_title === $newTitle) {
                    $this->unchangedCount++;
                } else {
                    $existing->update([
                        'discount_amount' => $newAmount,
                        'violation_title' => $newTitle,
                        'import_batch_id' => $this->batch->id,
                    ]);
                    $this->updatedCount++;
                }
            }
        } else {
            CompanyPenalty::create([
                'employee_id' => $empId,
                'payroll_month' => $this->payrollMonth(),
                'import_batch_id' => $this->batch->id,
                'violation_title' => $newTitle,
                'discount_amount' => $newAmount,
                'penalty_date' => $date,
                'notes' => $notes
            ]);
            $this->insertedCount++;
        }
    }
}
