<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\Platform;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class EmployeesImport implements ToCollection, WithHeadingRow
{
    public int $rowCount = 0;
    public int $failedCount = 0;
    public array $errors = [];
    protected ?\App\Models\ImportBatch $batch;
    protected bool $updateExisting;
    protected bool $addNew;

    public $insertedCount = 0;
    public $updatedCount = 0;
    public $unchangedCount = 0;

    public function __construct(?\App\Models\ImportBatch $batch = null, bool $updateExisting = true, bool $addNew = true)
    {
        $this->batch = $batch;
        $this->updateExisting = $updateExisting;
        $this->addNew = $addNew;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $rawRow = $row->toArray();
                
                // تجاهل الأسطر الفارغة تماماً
                if ($this->isEmptyRow($rawRow)) continue;

                $updateData = [];

                // قراءة البيانات من الأعمدة
                $iqamaNumber = $this->getValue($rawRow, ['iqama_number', 'iqama number', 'رقم الإقامة', 'iqama', 'الاقامة', 'رقم الاقامة']);
                $iqamaNumber = preg_replace('/\s+/', '', (string)$iqamaNumber); // تنظيف رقم الإقامة من أي مسافات أو أسطر جديدة
                if (empty($iqamaNumber)) {
                    throw new \Exception("رقم الإقامة مفقود.");
                }

                $existingEmployee = Employee::where('iqama_number', $iqamaNumber)->first();

                $name = $this->getValue($rawRow, ["rider's name", 'riders name', 'riders_name', 'name', 'اسم الموظف', 'الاسم', 'اسم السائق', 'السائق', 'اسم']);
                if (!empty($name)) {
                    $updateData['name_ar'] = $name;
                    $updateData['name_en'] = $name;
                } elseif (!$existingEmployee) {
                    throw new \Exception("اسم الموظف مفقود لموظف جديد.");
                }

                $contract = $this->getValue($rawRow, ['contract type', 'contract_type', 'نوع العقد', 'العقد']);
                if ($contract !== null) {
                    $contractStr = mb_strtolower(trim((string)$contract));
                    if (str_contains($contractStr, 'عمولة') || str_contains($contractStr, 'نسبة') || str_contains($contractStr, 'commission')) {
                        $updateData['contract_type'] = 'commission';
                    } elseif (str_contains($contractStr, 'كل') || str_contains($contractStr, 'both') || str_contains($contractStr, 'معا') || str_contains($contractStr, 'راتب وعمولة')) {
                        $updateData['contract_type'] = 'both';
                    } else {
                        $updateData['contract_type'] = 'salary';
                    }
                }

                $salarySystemStr = $this->getValue($rawRow, ['salary system', 'نظام الراتب']);
                if ($salarySystemStr !== null) {
                    $sysStr = str_replace(['ة', 'أ', 'إ', 'ا'], ['ه', 'ا', 'ا', 'ا'], mb_strtolower((string)$salarySystemStr));
                    if (str_contains($sysStr, 'commission') || str_contains($sysStr, 'عموله') || str_contains($sysStr, 'نسبه')) {
                        $updateData['salary_system'] = 'commission_tiered';
                    } elseif (str_contains($sysStr, 'hybrid') || str_contains($sysStr, 'هجين') || str_contains($sysStr, 'كل') || str_contains($sysStr, 'معا') || str_contains($sysStr, 'راتب وعموله')) {
                        $updateData['salary_system'] = 'hybrid';
                    } else {
                        $updateData['salary_system'] = 'fixed';
                    }
                }

                $salary = $this->getValue($rawRow, ['current basic salary', 'agreed salary', 'salary', 'الراتب', 'الراتب الأساسي', 'راتب']);
                if ($salary !== null) {
                    $salStr = str_replace(['ة', 'أ', 'إ', 'ا'], ['ه', 'ا', 'ا', 'ا'], mb_strtolower((string)$salary));
                    if (str_contains($salStr, 'عموله') || str_contains($salStr, 'commission') || str_contains($salStr, 'نسبه')) {
                        $updateData['salary_system'] = 'commission_tiered';
                        $updateData['agreed_salary'] = 0;
                    } else {
                        $numericSalary = is_numeric($salary) ? (float)$salary : 0;
                        $updateData['agreed_salary'] = $numericSalary;
                        
                        // Infer salary system if not explicitly set
                        if (!isset($updateData['salary_system'])) {
                            if ($numericSalary > 0 && $numericSalary < 1000) {
                                // e.g. 8, 15, 20 -> This is commission/per order price
                                $updateData['salary_system'] = 'commission_tiered';
                            } else {
                                // e.g. 1700, 2500 -> This is a fixed basic salary
                                $updateData['salary_system'] = 'fixed';
                            }
                        }
                    }
                }

                $statusKeys = ['employee status', 'status', 'الحالة', 'حالة الموظف', 'نشط', 'حالة'];
                if ($this->hasColumn($rawRow, $statusKeys)) {
                    $status = $this->getValue($rawRow, $statusKeys);
                    $employeeStatus = 'active';
                    if (is_string($status)) {
                        if (str_contains($status, 'غير نشط') || str_contains(strtolower($status), 'inactive') || $status == '0' || str_contains($status, 'لا')) $employeeStatus = 'inactive';
                        elseif (str_contains($status, 'موقوف') || str_contains(strtolower($status), 'suspended')) $employeeStatus = 'suspended';
                    }
                    $updateData['employee_status'] = $employeeStatus;
                }

                $branchKeys = ['branch', 'الفرع', 'فرع'];
                if ($this->hasColumn($rawRow, $branchKeys)) {
                    $branchName = $this->getValue($rawRow, $branchKeys);
                    $updateData['branch_id'] = null;
                    if (!empty($branchName)) {
                        $branch = Branch::firstOrCreate(['name' => $branchName]);
                        $updateData['branch_id'] = $branch->id;
                    }
                }

                $cityKeys = ['city', 'المدينة', 'مدينة'];
                if ($this->hasColumn($rawRow, $cityKeys)) {
                    $cityName = $this->getValue($rawRow, $cityKeys);
                    $updateData['city'] = $cityName ?: null;
                }

                $platformKeys = ['current application (guide only)', 'application', 'platform', 'التطبيق', 'تطبيق'];
                if ($this->hasColumn($rawRow, $platformKeys)) {
                    $platformStr = $this->getValue($rawRow, $platformKeys);
                    $updateData['platform_id'] = null;
                    if (!empty($platformStr)) {
                        $platform = Platform::where('name', 'LIKE', "%{$platformStr}%")
                                            ->orWhere('name_en', 'LIKE', "%{$platformStr}%")
                                            ->first();
                        if ($platform) {
                            $updateData['platform_id'] = $platform->id;
                        }
                    }
                }

                $appIdKeys = ['app id', 'app_id', 'المعرف', 'رقم التطبيق', 'رقم الكابتن', 'معرف الكابتن'];
                if ($this->hasColumn($rawRow, $appIdKeys)) {
                    $appIdStr = $this->getValue($rawRow, $appIdKeys);
                    if ($appIdStr === null || $appIdStr === '') {
                        $updateData['app_id'] = null;
                    } else {
                        // Extract app_id as string safely, just like captain_id
                        $strId = (string) $appIdStr;
                        if (preg_match('/^(\d+)\.0+$/', $strId, $m)) {
                            $updateData['app_id'] = $m[1];
                        } else {
                            $updateData['app_id'] = trim($strId);
                        }
                    }
                }

                $vehicleKeys = ['vehicle#', 'vehicle number', 'رقم المركبة'];
                if ($this->hasColumn($rawRow, $vehicleKeys)) {
                    $vehicleNumber = $this->getValue($rawRow, $vehicleKeys);
                    $updateData['vehicle_number'] = $vehicleNumber === null || $vehicleNumber === '' ? null : (string) $vehicleNumber;
                }

                $discountKeys = ['discount factor', 'discount_factor', 'عامل الخصم'];
                if ($this->hasColumn($rawRow, $discountKeys)) {
                    $discountFactor = $this->getValue($rawRow, $discountKeys);
                    $updateData['discount_factor'] = $discountFactor === null || $discountFactor === '' ? 0.0 : (is_numeric($discountFactor) ? (float) $discountFactor : 0.0);
                }

                $notesKeys = ['notes', 'note', 'ملاحظات', 'ملاحظة', 'الملاحظات'];
                if ($this->hasColumn($rawRow, $notesKeys)) {
                    $notes = $this->getValue($rawRow, $notesKeys);
                    $updateData['notes'] = $notes === null || $notes === '' ? null : $notes;
                }

                if ($this->batch) {
                    $updateData['import_batch_id'] = $this->batch->id;
                }

                // اكتشاف التطابق التام لمنع التحديث غير المبرر وتنبيه المستخدم
                if ($existingEmployee) {
                    if (!$this->updateExisting) {
                        $this->unchangedCount++;
                        $this->rowCount++;
                        continue;
                    }

                    $isDuplicate = true;
                    foreach ($updateData as $key => $value) {
                        if ($key === 'import_batch_id') continue;
                        
                        // معالجة المقارنة بدقة للأنواع المختلفة
                        if ($key === 'agreed_salary') {
                            if ((float)$existingEmployee->$key !== (float)$value) { $isDuplicate = false; break; }
                        } else {
                            if ($existingEmployee->$key != $value) { $isDuplicate = false; break; }
                        }
                    }

                    if ($isDuplicate) {
                        $this->unchangedCount++;
                        $this->rowCount++;
                        continue;
                    }
                } else {
                    if (!$this->addNew) {
                        $this->unchangedCount++;
                        $this->rowCount++;
                        continue;
                    }
                }

                // تحديث أو إنشاء الموظف
                $employee = Employee::updateOrCreate(
                    ['iqama_number' => $iqamaNumber],
                    $updateData
                );
                
                if ($employee->wasRecentlyCreated) {
                    $this->insertedCount++;
                } else {
                    $this->updatedCount++;
                }

                // تسجيل معرف التطبيق كـ EmployeePlatformId لضمان ربط الرواتب
                if (!empty($updateData['app_id']) && !empty($updateData['platform_id'])) {
                    $appIds = array_map('trim', explode(',', $updateData['app_id']));
                    foreach ($appIds as $singleAppId) {
                        if (empty($singleAppId)) continue;
                        
                        \App\Models\EmployeePlatformId::firstOrCreate([
                            'employee_id' => $employee->id,
                            'platform_id' => $updateData['platform_id'],
                            'captain_id'  => (string)$singleAppId,
                        ], [
                            'start_date'      => now()->startOfMonth(),
                            'import_batch_id' => $this->batch ? $this->batch->id : null,
                        ]);
                    }
                }

                $this->rowCount++;
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage() . " (" . json_encode($rawRow, JSON_UNESCAPED_UNICODE) . ")"
                ];
            }
        }
    }
    private function hasColumn(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            foreach ($row as $rowKey => $val) {
                $normalizedRowKey = $this->normalizeKey((string)$rowKey);
                if ($normalizedRowKey === $normalizedKey || str_starts_with($normalizedRowKey, $normalizedKey . '_')) {
                    return true;
                }
            }
        }
        return false;
    }

    private function getValue(array $row, array $keys)
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            foreach ($row as $rowKey => $val) {
                $normalizedRowKey = $this->normalizeKey((string)$rowKey);
                if ($normalizedRowKey === $normalizedKey || str_starts_with($normalizedRowKey, $normalizedKey . '_')) {
                    return $val;
                }
            }
        }
        return null;
    }

    private function normalizeKey(string $key): string
    {
        $key = mb_strtolower(trim($key));
        $key = str_replace([' ', '-', '+', '%', '.', '/', '\\', '(', ')', '*', '#', '&', '\''], '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        return trim($key, '_');
    }

    private function isEmptyRow(array $row): bool
    {
        $iqama = $this->getValue($row, ['iqama_number', 'iqama number', 'رقم الإقامة', 'iqama']);
        $name  = $this->getValue($row, ["rider's name", 'riders name', 'name', 'اسم الموظف', 'الاسم']);
        
        return empty($iqama) && empty($name);
    }
}
