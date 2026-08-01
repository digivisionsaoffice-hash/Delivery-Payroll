<?php

namespace App\Imports;

use App\Models\Advance;
use App\Models\TrafficViolation;
use App\Models\SparePartsMisuse;

class UnifiedDeductionsImport extends BaseDeductionImport
{
    protected function saveRow(int $empId, $row): void
    {
        $rawArr = is_array($row) ? $row : $row->toArray();
        $type = mb_strtolower((string)$this->getValue($rawArr, ['type', 'النوع', 'نوع الخصم', 'type ']), 'UTF-8');
        $amount = (float)$this->getValue($rawArr, ['amount', 'المبلغ', 'القيمة', 'amount ']);

        if (str_contains($type, 'سلف') || str_contains($type, 'advance') || str_contains($type, 'cash')) {
            $existing = Advance::where('employee_id', $empId)->where('payroll_month', $this->payrollMonth())->first();
            if ($existing) {
                if ($existing->import_batch_id === $this->batch->id) {
                    $existing->update(['amount' => $existing->amount + $amount]);
                    $this->updatedCount++;
                } else {
                    if ((float)$existing->amount === (float)$amount) {
                        $this->unchangedCount++;
                    } else {
                        $existing->update(['amount' => $amount, 'import_batch_id' => $this->batch->id]);
                        $this->updatedCount++;
                    }
                }
            } else {
                Advance::create(['employee_id' => $empId, 'payroll_month' => $this->payrollMonth(), 'import_batch_id' => $this->batch->id, 'amount' => $amount]);
                $this->insertedCount++;
            }
        } elseif (str_contains($type, 'مخالف') || str_contains($type, 'violation') || str_contains($type, 'traffic') || str_contains($type, 'penalty')) {
            $existing = TrafficViolation::where('employee_id', $empId)->where('payroll_month', $this->payrollMonth())->first();
            if ($existing) {
                if ($existing->import_batch_id === $this->batch->id) {
                    $existing->update(['amount' => $existing->amount + $amount]);
                    $this->updatedCount++;
                } else {
                    if ((float)$existing->amount === (float)$amount) {
                        $this->unchangedCount++;
                    } else {
                        $existing->update(['amount' => $amount, 'import_batch_id' => $this->batch->id]);
                        $this->updatedCount++;
                    }
                }
            } else {
                TrafficViolation::create(['employee_id' => $empId, 'payroll_month' => $this->payrollMonth(), 'import_batch_id' => $this->batch->id, 'amount' => $amount]);
                $this->insertedCount++;
            }
        } elseif (str_contains($type, 'غيار') || str_contains($type, 'spare') || str_contains($type, 'part') || str_contains($type, 'misuse')) {
            $existing = SparePartsMisuse::where('employee_id', $empId)->where('payroll_month', $this->payrollMonth())->first();
            if ($existing) {
                if ($existing->import_batch_id === $this->batch->id) {
                    $existing->update(['cost' => $existing->cost + $amount, 'total_value' => $existing->total_value + $amount]);
                    $this->updatedCount++;
                } else {
                    if ((float)$existing->total_value === (float)$amount) {
                        $this->unchangedCount++;
                    } else {
                        $existing->update(['cost' => $amount, 'total_value' => $amount, 'import_batch_id' => $this->batch->id]);
                        $this->updatedCount++;
                    }
                }
            } else {
                SparePartsMisuse::create(['employee_id' => $empId, 'payroll_month' => $this->payrollMonth(), 'import_batch_id' => $this->batch->id, 'cost' => $amount, 'total_value' => $amount, 'quantity' => 1]);
                $this->insertedCount++;
            }
        } else {
            // Default to advances if the type is completely unrecognized
            $existing = Advance::where('employee_id', $empId)->where('payroll_month', $this->payrollMonth())->first();
            if ($existing) {
                if ($existing->import_batch_id === $this->batch->id) {
                    $existing->update(['amount' => $existing->amount + $amount, 'notes' => $existing->notes . ' | نوع غير معروف: ' . $type]);
                    $this->updatedCount++;
                } else {
                    if ((float)$existing->amount === (float)$amount) {
                        $this->unchangedCount++;
                    } else {
                        $existing->update(['amount' => $amount, 'notes' => 'نوع غير معروف: ' . $type, 'import_batch_id' => $this->batch->id]);
                        $this->updatedCount++;
                    }
                }
            } else {
                Advance::create(['employee_id' => $empId, 'payroll_month' => $this->payrollMonth(), 'import_batch_id' => $this->batch->id, 'amount' => $amount, 'notes' => 'نوع غير معروف: ' . $type]);
                $this->insertedCount++;
            }
        }
    }
}
