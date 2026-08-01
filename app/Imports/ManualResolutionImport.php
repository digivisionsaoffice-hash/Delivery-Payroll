<?php

namespace App\Imports;

use App\Models\AppDailyRecord;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ManualResolutionImport implements ToCollection, WithHeadingRow
{
    protected $batchId;
    public $resolvedCount = 0;
    public $failedCount = 0;
    public $failedRows = [];

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $rowArray = $row->toArray();
            $keys = array_keys($rowArray);
            
            // Support different column names for Iqama
            $iqama = trim($rowArray['rkm_alaqam_almrbott'] ?? $rowArray['rkm_alakam'] ?? $rowArray['rkm_alakam_almrbott'] ?? $rowArray['rkm_alaqam'] ?? $rowArray['rkm_alaqamh'] ?? $rowArray['rkm_alaqam_1'] ?? $rowArray['iqama_number'] ?? $rowArray['iqama'] ?? '');
            
            // If still empty, try to get from the 17th column (index 16) or the last column
            if (!$iqama && count($keys) > 0) {
                // If there's 17 columns, the 17th is Iqama (index 16)
                if (isset($keys[16])) {
                    $iqama = trim($rowArray[$keys[16]] ?? '');
                }
                
                // If still empty, check the very last column
                if (!$iqama) {
                    $lastKey = end($keys);
                    $iqama = trim($rowArray[$lastKey] ?? '');
                }
            }

            // Support different column names for Captain ID
            $captainId = trim($rowArray['captain_id'] ?? $rowArray['kaptn_ayd'] ?? '');
            
            // If empty, Captain ID is usually the 7th column (index 6)
            if (!$captainId && isset($keys[6])) {
                $captainId = trim($rowArray[$keys[6]] ?? '');
            }

            // For date, support different variants
            $dateVal = $rowArray['altarykh'] ?? $rowArray['altaryx'] ?? $rowArray['record_date'] ?? $rowArray['date'] ?? null;
            
            // If empty, Date is usually the 3rd column (index 2)
            if (!$dateVal && isset($keys[2])) {
                $dateVal = $rowArray[$keys[2]] ?? null;
            }

            $recordDate = null;
            if ($dateVal) {
                if (is_numeric($dateVal)) {
                    $recordDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateVal)->format('Y-m-d');
                } else {
                    try {
                        $recordDate = \Carbon\Carbon::parse(str_replace('/', '-', $dateVal))->format('Y-m-d');
                    } catch (\Exception $e) {}
                }
            }

            if (!$iqama || !$captainId || !$recordDate) {
                continue;
            }

            // Remove any spaces from Iqama
            $iqama = preg_replace('/\s+/', '', $iqama);

            // Verify if Iqama exists in employees
            $employee = Employee::where('iqama_number', $iqama)->first();
            if (!$employee) {
                $this->failedCount++;
                $this->failedRows[] = $rowArray;
                continue;
            }

            // Find the unresolved record and update it
            $updated = AppDailyRecord::where('import_batch_id', $this->batchId)
                ->where('captain_id', $captainId)
                ->where('record_date', $recordDate)
                ->where(function ($q) {
                    $q->whereNull('resolved_iqama')->orWhere('resolved_iqama', '')->orWhere('resolve_method', 'unresolved');
                })
                ->update([
                    'resolved_iqama' => $iqama,
                    'employee_id' => $employee->id,
                    'resolve_method' => 'manual_excel'
                ]);

            if ($updated) {
                $this->resolvedCount++;
            } else {
                // It might not be unresolved, or doesn't exist
            }
        }
    }
}
