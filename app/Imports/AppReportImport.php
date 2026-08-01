<?php

namespace App\Imports;

use App\Models\AppDailyRecord;
use App\Models\ImportBatch;
use App\Support\PlatformColumnMap;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * ===================================================================
 * AppReportImport — استيراد ذكي لتقارير التطبيقات
 * ===================================================================
 * نوع الورقة في DB دائماً: app_report
 * شكل الأعمدة يُحدَّد من: platform.report_format
 *
 * يكتشف الأعمدة غير المعروفة وينبّه عليها
 */
class AppReportImport implements ToCollection, WithHeadingRow
{
    protected ImportBatch $batch;
    protected string $reportFormat = 'ninja';
    protected int $rowCount        = 0;
    protected int $failedCount     = 0;
    protected array $errors        = [];
    protected array $unknownColumns = [];

    public function setBatch(ImportBatch $batch): static
    {
        $this->batch        = $batch;
        // الحصول على report_format من المنصة
        $this->reportFormat = $batch->platform->report_format ?? 'ninja';
        return $this;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        // اكتشاف الأعمدة غير المعروفة من الصف الأول
        $firstRow = $rows->first();
        if ($firstRow) {
            $headers = array_keys($firstRow->toArray());
            $this->unknownColumns = PlatformColumnMap::detectUnknownColumns(
                $headers,
                $this->reportFormat
            );
        }

        foreach ($rows as $rawRow) {
            try {
                $rawArr = $rawRow->toArray();
                if ($this->isEmptyRow($rawArr)) continue;

                // التعرف الذكي على الأعمدة بناءً على report_format
                $row = PlatformColumnMap::resolveRow($rawArr, $this->reportFormat, true);

                // تحويل التاريخ مع استخدام شهر الدفعة كبديل إذا كان فارغاً
                $date = $this->parseDate($row['date'] ?? null);
                if (!$date && $this->batch && $this->batch->month) {
                    $date = $this->batch->month->format('Y-m-d');
                }

                if (!$date) {
                    $this->failedCount++;
                    $this->errors[] = [
                        'row'     => $rawArr,
                        'message' => 'تاريخ غير صالح ولا يمكن تحديد شهر الدفعة: ' . ($row['date'] ?? 'فارغ'),
                    ];
                    continue;
                }

                // captain_id دائماً كـ string (يمنع قطع الأرقام الطويلة)
                $captainId = $this->parseCaptainId($row['captain_id'] ?? null);

                // استخراج قيم كيتا شرائح بشكل منفصل
                $bonusCapacity = $this->parseFloat($row['bonus_capacity'] ?? null);
                $bonusTrial    = $this->parseFloat($row['bonus_trial'] ?? null);
                $foodDamage    = $this->parseFloat($row['food_damage'] ?? null);
                $tgaDiscount   = $this->parseFloat($row['tga_discount'] ?? null);

                // تجميع بونص التطبيقات الأخرى (أو إبقاء دمج كيتا لحقل bonus_ftr للتوافق الرجعي لو لزم الأمر)
                $bonusFtr = $this->parseBonusFtr($row);

                // تحليل Adjustments
                $adjustments    = $this->parseFloat($row['adjustments'] ?? null);
                $suppliersCosts = $this->parseFloat($row['suppliers_costs'] ?? null);

                // هل هذا سطر تسوية؟
                $isSettlement = ($suppliersCosts == 0 && $adjustments != 0);

                // نعتمد على التطبيق والتاريخ والسائق (والوردية إن وجدت) كمعيار لعدم التكرار
                $matchAttributes = [
                    'platform_id' => $this->batch->platform_id,
                    'record_date' => $date,
                    'captain_id'  => $captainId,
                    'shift_id'    => $row['shift_id'] ?? null,
                ];

                $values = [
                    'import_batch_id'  => $this->batch->id,
                    'supplier_id'      => $row['supplier_id']     ?? null,
                    'supplier_name'    => $row['supplier_name']   ?? null,
                    'contract_type'    => $row['contract_type']   ?? null,
                    'captain_name'     => $row['captain_name']    ?? null,
                    'branch_name'      => $row['branch_name']     ?? null,
                    'wallet_note'      => $row['wallet_note']     ?? null,
                    'working_hours'    => $this->parseFloat($row['working_hours']    ?? null),
                    'dynamic_per_hour' => $this->parseFloat($row['dynamic_per_hour'] ?? null),
                    'orders'           => (int)($row['orders']   ?? 0),
                    'suppliers_costs'  => $suppliersCosts,
                    'bonus_ftr'        => $bonusFtr,
                    'bonus_capacity'   => $bonusCapacity,
                    'bonus_trial'      => $bonusTrial,
                    'food_damage'      => $foodDamage,
                    'tga_discount'     => $tgaDiscount,
                    'adjustments'      => $adjustments,
                    'net_cost'         => $this->parseFloat($row['net_cost']   ?? null),
                    'vat_15'           => $this->parseFloat($row['vat_15']     ?? null),
                    'total_dues'       => $this->parseFloat($row['total_dues'] ?? null),
                    'is_settlement'    => $isSettlement,
                ];

                // يجب إضافة ملاحظة المحفظة وقيمة التسوية لمنع دمج تسويتين مختلفتين لنفس المندوب في نفس اليوم
                if ($isSettlement) {
                    $matchAttributes['adjustments'] = $adjustments;
                    $matchAttributes['wallet_note'] = $row['wallet_note'] ?? null;
                }

                AppDailyRecord::updateOrCreate($matchAttributes, $values);

                // تحديث بيانات الموظف (المدينة والحالة)
                $this->updateEmployeeData($captainId, $row);

                $this->rowCount++;
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = [
                    'row'     => $rawArr,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    protected array $cachedEmployees = [];

    protected function updateEmployeeData($captainId, array $row)
    {
        if (!array_key_exists($captainId, $this->cachedEmployees)) {
            $empPlatform = \App\Models\EmployeePlatformId::with('employee')
                ->where('platform_id', $this->batch->platform_id)
                ->where('captain_id', $captainId)
                ->first();
                
            $this->cachedEmployees[$captainId] = $empPlatform ? $empPlatform->employee : null;
        }

        $employee = $this->cachedEmployees[$captainId];
        
        if ($employee) {
            $updates = [];
            
            $cityName = $row['branch_name'] ?? null; // PlatformColumnMap maps city/branch to branch_name
            if (!empty($cityName) && $employee->city !== $cityName) {
                $updates['city'] = $cityName;
                $employee->city = $cityName; // Update local instance
            }

            // Since the employee has records in the app report, they are active
            if ($employee->employee_status !== 'active') {
                $updates['employee_status'] = 'active';
                $employee->employee_status = 'active'; // Update local instance
            }

            // تحديث المنصة والمعرّف
            if ($employee->platform_id !== $this->batch->platform_id) {
                $updates['platform_id'] = $this->batch->platform_id;
                $employee->platform_id = $this->batch->platform_id;
            }

            if ($employee->app_id !== $captainId) {
                $updates['app_id'] = $captainId;
                $employee->app_id = $captainId;
            }

            if (!empty($updates)) {
                // Perform a direct update to avoid triggering unnecessary events multiple times
                \App\Models\Employee::where('id', $employee->id)->update($updates);
            }
        }
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception) {}
        }

        $strValue = (string) $value;
        $arabicMonths = [
            'يناير' => 'January', 'فبراير' => 'February', 'مارس' => 'March',
            'أبريل' => 'April', 'ابريل' => 'April', 'مايو' => 'May',
            'يونيو' => 'June', 'يوليو' => 'July', 'أغسطس' => 'August',
            'اغسطس' => 'August', 'سبتمبر' => 'September', 'أكتوبر' => 'October',
            'اكتوبر' => 'October', 'نوفمبر' => 'November', 'ديسمبر' => 'December'
        ];
        $strValue = strtr($strValue, $arabicMonths);

        try {
            return Carbon::parse($strValue)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function parseCaptainId(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        $str = (string) $value;
        // إزالة الصفر العشري الذي يُضيفه Excel (مثل "12345.0")
        if (preg_match('/^(\d+)\.0+$/', $str, $m)) {
            return $m[1];
        }
        return trim($str);
    }

    private function parseBonusFtr(array $row): float
    {
        // تم فصل حافز السعة والتجربة لحقول مستقلة لكيتا، لكن يمكن إرجاع مجموعهم للتوافق الرجعي 
        if ($this->reportFormat === 'keeta_slabs') {
            return $this->parseFloat($row['bonus_capacity'] ?? null)
                 + $this->parseFloat($row['bonus_trial']    ?? null);
        }
        return $this->parseFloat($row['bonus_ftr'] ?? null);
    }

    private function parseFloat(mixed $value): float
    {
        if ($value === null || $value === '') return 0.0;
        
        // إزالة أي نصوص (مثل العملات SAR, ر.س) والإبقاء فقط على الأرقام والفاصلة العشرية وعلامة السالب
        $str = preg_replace('/[^\d\.\-]/', '', (string)$value);
        
        return round((float)$str, 2);
    }

    private function isEmptyRow(array $row): bool
    {
        // نعتبر الصف فارغاً إذا كانت كل القيم فارغة تماماً
        foreach ($row as $val) {
            if ($val !== null && $val !== '') {
                return false;
            }
        }
        return true;
    }

    public function getRowCount(): int         { return $this->rowCount;       }
    public function getFailedCount(): int      { return $this->failedCount;    }
    public function getErrors(): array         { return array_slice($this->errors, 0, 20); }
    public function getUnknownColumns(): array { return $this->unknownColumns; }
}
