<?php
// قالب مشترك لكل importers الخصومات
namespace App\Imports;

use App\Models\Employee;
use App\Models\ImportBatch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

abstract class BaseDeductionImport implements ToCollection, WithHeadingRow
{
    protected ImportBatch $batch;
    protected int $rowCount = 0, $failedCount = 0;
    public int $insertedCount = 0;
    public int $updatedCount = 0;
    public int $unchangedCount = 0;
    protected array $errors = [];

    public function setBatch(ImportBatch $batch): static { $this->batch = $batch; return $this; }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $rawRow = $row->toArray();
                $iqamaRaw = $this->getValue($rawRow, ['iqama_number', 'iqama number', 'رقم الإقامة', 'iqama', 'الاقامة', 'رقم الاقامة', 'national_id', 'id_number']);
                $iqama = preg_replace('/\s+/', '', (string)$iqamaRaw);
                // If Iqama is completely empty, it might be an empty trailing row from Excel. We just skip it without counting it as an error to avoid fake errors.
                if (!$iqama) { continue; }
                
                $employee = Employee::where('iqama_number', $iqama)->first();
                if (!$employee) { 
                    $this->failedCount++; 
                    $this->errors[] = ['row' => $row->toArray(), 'message' => "إقامة الموظف ({$iqama}) غير مضافة في بيانات الموظفين، يرجى إضافته أولاً."]; 
                    continue; 
                }
                $this->saveRow($employee->id, $row);
                $this->rowCount++;
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = ['row' => $row->toArray(), 'message' => $e->getMessage()];
            }
        }
    }

    abstract protected function saveRow(int $employeeId, Collection|\ArrayAccess $row): void;

    protected function getValue(array $row, array $keys)
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            foreach ($row as $rowKey => $val) {
                if ($this->normalizeKey((string)$rowKey) === $normalizedKey) {
                    return $val;
                }
            }
        }
        return null;
    }

    protected function normalizeKey(string $key): string
    {
        $key = mb_strtolower(trim($key));
        $key = str_replace([' ', '-', '+', '%', '.', '/', '\\', '(', ')', '*', '#', '&', '\''], '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        return trim($key, '_');
    }

    protected function payrollMonth(): string
    {
        return $this->batch->month->format('Y-m-01');
    }

    public function getRowCount(): int    { return $this->rowCount; }
    public function getFailedCount(): int { return $this->failedCount; }
    public function getErrors(): array    { return array_slice($this->errors, 0, 20); }
}
