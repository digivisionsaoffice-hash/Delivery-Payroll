<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeePlatformId;
use App\Models\ImportBatch;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class IdChangesImport implements ToCollection, WithHeadingRow
{
    protected ImportBatch $batch;
    protected int $rowCount = 0, $failedCount = 0;
    public int $insertedCount = 0, $updatedCount = 0;
    protected array $errors = [];
    protected array $warnings = [];

    public function setBatch(ImportBatch $batch): static { $this->batch = $batch; return $this; }

    public function collection(Collection $rows)
    {
        // 1. فحص تعدد الـ IDs لنفس الإقامة في حال وجود تسويات
        $iqamaCaptainIds = [];
        foreach ($rows as $rawRow) {
            $rawArr = $rawRow->toArray();
            $row = \App\Support\PlatformColumnMap::resolveRow($rawArr, 'id_changes', false);
            
            $iqama = preg_replace('/\s+/', '', (string)($row['iqama_number'] ?? ''));
            $rawCaptainId = (string)($row['captain_id'] ?? '');
            $captainId = preg_match('/^(\d+)\.0+$/', $rawCaptainId, $m) ? $m[1] : trim($rawCaptainId);
            $adj = isset($row['adjustment']) && is_numeric($row['adjustment']) ? (float) $row['adjustment'] : null;
            
            if ($iqama && $captainId && $adj !== null) {
                $iqamaCaptainIds[$iqama][$captainId] = true;
            }
        }

        foreach ($iqamaCaptainIds as $iqama => $cIds) {
            if (count($cIds) > 1 && !request()->has('ignore_multiple_ids_warning')) {
                $this->failedCount++;
                $this->errors[] = ['message' => "تحذير: الإقامة ({$iqama}) مرتبطة بأكثر من معرف (".count($cIds)." IDs) عليها تسويات في هذا الملف! سيتحمل هذا الموظف خصومات/تسويات كل هذه المعرفات. إذا كنت متأكداً، فعّل خيار التجاهل من الشاشة وحاول مجدداً."];
                return; // إيقاف المعالجة فوراً للفت انتباه المستخدم
            }
        }

        // 2. معالجة السطور
        foreach ($rows as $rawRow) {
            try {
                $rawArr = $rawRow->toArray();
                
                // تخطي الصفوف الفارغة تماماً
                $isEmpty = true;
                foreach ($rawArr as $val) {
                    if ($val !== null && $val !== '') { $isEmpty = false; break; }
                }
                if ($isEmpty) continue;

                $row = \App\Support\PlatformColumnMap::resolveRow($rawArr, 'id_changes', false);

                $iqama = preg_replace('/\s+/', '', (string)($row['iqama_number'] ?? ''));
                $rawCaptainId = (string)($row['captain_id'] ?? '');
                $captainId = preg_match('/^(\d+)\.0+$/', $rawCaptainId, $m) ? $m[1] : trim($rawCaptainId);
                
                if (empty($iqama) && empty($captainId)) {
                    continue; // قد يكون صف فارغ آخر
                }

                if (!$iqama || !$captainId) { 
                    $this->failedCount++; 
                    $this->errors[] = ['message' => 'رقم الإقامة أو المعرف مفقود', 'row' => $rawArr];
                    continue; 
                }

                $employee = Employee::where('iqama_number', $iqama)->first();

                if (!$employee) {
                    $this->failedCount++;
                    $this->errors[] = ['message' => "إقامة الموظف ({$iqama}) غير مضافة في بيانات الموظفين، يرجى إضافته أولاً.", 'row' => $rawArr];
                    continue;
                }

                $startDate = null; $endDate = null;
                if (!empty($row['start_date'])) {
                    $v = $row['start_date'];
                    $startDate = is_numeric($v)
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d')
                        : Carbon::parse($v)->format('Y-m-d');
                } else {
                    $startDate = $this->batch->month->format('Y-m-d');
                }
                
                if (!empty($row['end_date'])) {
                    $v = $row['end_date'];
                    $endDate = is_numeric($v)
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d')
                        : Carbon::parse($v)->format('Y-m-d');
                }

                $idName = !empty($row['id_name']) ? $row['id_name'] : ($employee->name_ar ?? $employee->name_en);

                $adj = isset($row['adjustment']) && is_numeric($row['adjustment']) ? (float) $row['adjustment'] : null;
                $orders = isset($row['orders']) && is_numeric($row['orders']) ? (int) $row['orders'] : 0;
                $revenue = isset($row['unknown_revenue']) && is_numeric($row['unknown_revenue']) ? (float) $row['unknown_revenue'] : 0;
                
                $bannedVal = strtolower(trim($row['banned'] ?? ''));
                $isBanned = in_array($bannedVal, ['yes', '1', 'نعم', 'true', 'y', 'محظور', 'حظر', 'انحظر']);

                // نظام ذكي: التحقق من التضارب (هل الموظف يعمل بـ ID آخر في نفس الفترة؟)
                // نستثني سطور التسويات (الخالصة) من فحص التضارب لأن التسوية قد تنزل للموظف على ID قديم في يوم يعمل فيه بـ ID جديد.
                // السطر يعتبر "تسوية خالصة" إذا لم يحتوي على طلبات ولا إيراد، وكان يحتوي على قيمة تسوية.
                $isAdjustmentOnly = ($adj !== null && $adj != 0) && ($orders == 0 && $revenue == 0);

                if (!$isAdjustmentOnly) {
                    $incomingStart = $startDate ?? '1000-01-01';
                    $incomingEnd = $endDate ?? '9999-12-31';
                    $qIncomingStart = $incomingStart === '1000-01-01' ? $incomingStart : $incomingStart . ' 00:00:00';
                    $qIncomingEnd = $incomingEnd === '9999-12-31' ? $incomingEnd : $incomingEnd . ' 23:59:59';
                    $shouldSave = true;

                    // 1. تحقق إضافي (أولوية قصوى): هل المعرف (captain_id) مسجل لموظف آخر في نفس الفترة؟ (شخصين على نفس الآي دي)
                    $idConflictRecord = EmployeePlatformId::where('platform_id', $this->batch->platform_id)
                        ->where('captain_id', $captainId)
                        ->where('employee_id', '!=', $employee->id) // موظف آخر
                        ->whereRaw("GREATEST(COALESCE(start_date, '1000-01-01'), ?) <= LEAST(COALESCE(end_date, '9999-12-31'), ?)", [$qIncomingStart, $qIncomingEnd])
                        ->with('employee')
                        ->first();

                    if ($idConflictRecord) {
                        $overlapStart = max($incomingStart, $idConflictRecord->start_date ?? '1000-01-01');
                        $overlapEnd = min($incomingEnd, $idConflictRecord->end_date ?? '9999-12-31');

                        $hasActivity = \App\Models\AppDailyRecord::where('captain_id', $captainId)
                            ->whereBetween('record_date', [$overlapStart, $overlapEnd])
                            ->where(function($q) { $q->where('working_hours', '>', 0)->orWhere('orders', '>', 0); })
                            ->exists();

                        $empName = $employee->name_ar ?? $employee->name_en;
                        $otherEmpName = $idConflictRecord->employee->name_ar ?? $idConflictRecord->employee->name_en;

                        if ($hasActivity) {
                            $inStartTs = strtotime($incomingStart);
                            $inEndTs = strtotime($incomingEnd);
                            $inDuration = ($inEndTs - $inStartTs) / 86400 + 1;

                            $cStartTs = $idConflictRecord->start_date ? strtotime($idConflictRecord->start_date->format('Y-m-d')) : strtotime('1000-01-01');
                            $cEndTs = $idConflictRecord->end_date ? strtotime($idConflictRecord->end_date->format('Y-m-d')) : strtotime('9999-12-31');
                            $cDuration = ($cEndTs - $cStartTs) / 86400 + 1;

                            // استثناء ذكي: إذا كانت إحدى الفترتين قصيرة جداً والأخرى طويلة
                            if (min($inDuration, $cDuration) <= 7 && max($inDuration, $cDuration) >= 15) {
                                $this->warnings[] = [
                                    'iqama' => $iqama,
                                    'message' => "تنبيه (استثناء ذكي): المعرف مسجل للموظف ({$otherEmpName}) والموظف ({$empName}). بما أن إحدى الفترتين قصيرة داخل فترة طويلة للآخر، النظام سيحتسب الأيام القصيرة لصاحبها ويستبعدها من الآخر تلقائياً.",
                                    'row' => $rawArr,
                                    'conflict_record' => $idConflictRecord->toArray()
                                ];
                            } else {
                                $this->failedCount++;
                                $this->errors[] = [
                                    'iqama' => $iqama,
                                    'message' => "إيقاف (تأثير مالي): المعرف ({$captainId}) مسجل للموظف ({$otherEmpName}) والموظف ({$empName}) ويوجد له عمليات فعلية! النظام يوقفها لمنع ظلم أي موظف للآخر.",
                                    'row' => $rawArr,
                                    'conflict_record' => $idConflictRecord->toArray()
                                ];
                                $shouldSave = false;
                            }
                        } else {
                            $this->warnings[] = [
                                'iqama' => $iqama,
                                'message' => "تنبيه: المعرف ({$captainId}) مسجل لموظفين ولكن لا يوجد له عمليات خلال التداخل. تم السماح.",
                                'row' => $rawArr,
                                'conflict_record' => $idConflictRecord->toArray()
                            ];
                        }
                    }

                    // 2. التحقق من التضارب للموظف نفسه (هل الموظف يعمل بـ ID آخر في نفس الفترة؟)
                    $overlappingRecord = EmployeePlatformId::where('employee_id', $employee->id)
                        ->where('platform_id', $this->batch->platform_id)
                        ->where('captain_id', '!=', $captainId) // معرف آخر
                        ->whereRaw("GREATEST(COALESCE(start_date, '1000-01-01'), ?) <= LEAST(COALESCE(end_date, '9999-12-31'), ?)", [$qIncomingStart, $qIncomingEnd])
                        ->first();

                    if ($overlappingRecord) {
                        $empName = $employee->name_ar ?? $employee->name_en;

                        if ($isBanned) {
                            $this->warnings[] = [
                                'iqama' => $iqama,
                                'message' => "تنبيه: تم السماح بتداخل تواريخ للموظف ({$empName}) بين المعرفين ({$captainId}) و ({$overlappingRecord->captain_id}) بفضل إقرار الحظر (banned).",
                                'row' => $rawArr,
                                'conflict_record' => $overlappingRecord->toArray()
                            ];
                        } else {
                            $overlapStart = max($incomingStart, $overlappingRecord->start_date ?? '1000-01-01');
                            $overlapEnd = min($incomingEnd, $overlappingRecord->end_date ?? '9999-12-31');

                            $datesId1 = \App\Models\AppDailyRecord::where('captain_id', $overlappingRecord->captain_id)
                                ->whereBetween('record_date', [$overlapStart, $overlapEnd])
                                ->where(function($q) { $q->where('working_hours', '>', 0)->orWhere('orders', '>', 0); })
                                ->pluck('record_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))->toArray();

                            $datesId2 = \App\Models\AppDailyRecord::where('captain_id', $captainId)
                                ->whereBetween('record_date', [$overlapStart, $overlapEnd])
                                ->where(function($q) { $q->where('working_hours', '>', 0)->orWhere('orders', '>', 0); })
                                ->pluck('record_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))->toArray();

                            if (count($datesId1) == 0) {
                                $this->warnings[] = [
                                    'iqama' => $iqama,
                                    'message' => "تنبيه: تداخل تواريخ للموظف ({$empName}) بين المعرفين ({$captainId}) و ({$overlappingRecord->captain_id}). لم يسجل المعرف الأول أي عمليات في التداخل وتم الاعتماد (سماح).",
                                    'row' => $rawArr,
                                    'conflict_record' => $overlappingRecord->toArray()
                                ];
                            } else {
                                if (count($datesId2) > 1) {
                                    $this->warnings[] = [
                                        'iqama' => $iqama,
                                        'message' => "تنبيه: الموظف ({$empName}) لديه عمليات بالمعرفين. وبما أن المعرف الثاني استخدم لأكثر من يوم، يُعتبر انتقال/حظر وتم السماح.",
                                        'row' => $rawArr,
                                        'conflict_record' => $overlappingRecord->toArray()
                                    ];
                                } else {
                                    $this->warnings[] = [
                                        'iqama' => $iqama,
                                        'message' => "تحذير: الموظف ({$empName}) لديه شغل على المعرف الأول واستخدم المعرف الثاني ({$captainId}) ليوم واحد فقط في فترة التداخل! تم السماح، يرجى المراجعة.",
                                        'row' => $rawArr,
                                        'conflict_record' => $overlappingRecord->toArray()
                                    ];
                                }
                            }
                        }
                    }
                }

                if ($shouldSave) {
                    $record = EmployeePlatformId::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'platform_id' => $this->batch->platform_id,
                            'captain_id'  => (string)$captainId,
                        ],
                        [
                            'start_date' => $startDate,
                            'id_name' => $idName, 
                            'end_date' => $endDate, 
                            'city' => $row['city'] ?? null, 
                            'import_batch_id' => $this->batch->id,
                            'adjustment_amount' => $adj
                        ]
                    );

                    if ($record->wasRecentlyCreated) {
                        $this->insertedCount++;
                    } else {
                        $this->updatedCount++;
                    }

                    // تحديث بيانات الموظف (التطبيق، المعرّف، المدينة، الحالة)
                    $updates = [];
                    $cityName = $row['city'] ?? null;
                    if (!empty($cityName) && $employee->city !== $cityName) {
                        $updates['city'] = $cityName;
                    }
                    $status = $isBanned ? 'inactive' : 'active';
                    if ($employee->employee_status !== $status) {
                        $updates['employee_status'] = $status;
                    }
                    if ($employee->platform_id !== $this->batch->platform_id) {
                        $updates['platform_id'] = $this->batch->platform_id;
                    }
                    if ($employee->app_id !== $captainId) {
                        $updates['app_id'] = $captainId;
                    }
                    if (!empty($updates)) {
                        \App\Models\Employee::where('id', $employee->id)->update($updates);
                    }

                    $this->rowCount++;
                }
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = ['message' => $e->getMessage()];
            }
        }
    }

    public function getRowCount(): int { return $this->rowCount; }
    public function getFailedCount(): int { return $this->failedCount; }
    public function getErrors(): array { return array_slice($this->errors, 0, 20); }
    public function getWarnings(): array { return array_slice($this->warnings, 0, 20); }
}
