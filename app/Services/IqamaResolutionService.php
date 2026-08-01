<?php

namespace App\Services;

use App\Models\AppDailyRecord;
use App\Models\Employee;
use App\Models\EmployeeIdDailyRecord;
use App\Models\EmployeePlatformId;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IqamaResolutionService
{
    /**
     * ===================================================================
     * المعالجة 1: توسيع نطاق تواريخ كل ID إلى سجل يومي
     * (محاكاة Power Query: from_date → to_date → يوم بيوم)
     * ===================================================================
     */
    public function expandIdRanges(int $platformId, string $month): int
    {
        EmployeeIdDailyRecord::where('platform_id', $platformId)
            ->where('month', $month . '-01')
            ->delete();

        $platformIds = EmployeePlatformId::where('platform_id', $platformId)
            ->whereNotNull('captain_id')
            ->with('employee')
            ->get();

        $dailyBest = [];

        foreach ($platformIds as $pid) {
            if (!$pid->employee) continue;

            $startTs = strtotime($pid->start_date->format('Y-m-d'));
            $endTs = $pid->end_date ? strtotime($pid->end_date->format('Y-m-d')) : false;
            $duration = $endTs ? round(($endTs - $startTs) / 86400) + 1 : 9999;

            $loopStart = max($startTs, strtotime($month . '-01'));
            $loopEnd = $pid->end_date
                ? min($endTs, strtotime(date('Y-m-t', strtotime($month))))
                : strtotime(date('Y-m-t', strtotime($month)));

            for ($ts = $loopStart; $ts <= $loopEnd; $ts = strtotime('+1 day', $ts)) {
                $dateStr = date('Y-m-d', $ts);
                $key = $pid->captain_id . '_' . $dateStr;

                if (!isset($dailyBest[$key]) || $duration < $dailyBest[$key]['duration']) {
                    $dailyBest[$key] = [
                        'platform_id'  => $platformId,
                        'employee_id'  => $pid->employee_id,
                        'iqama_number' => $pid->employee->iqama_number,
                        'captain_id'   => $pid->captain_id,
                        'work_date'    => $dateStr,
                        'month'        => $month . '-01',
                        'duration'     => $duration
                    ];
                }
            }
        }

        $records = array_map(function($item) {
            unset($item['duration']);
            return $item;
        }, array_values($dailyBest));

        $chunks = array_chunk($records, 500);
        foreach ($chunks as $chunk) {
            EmployeeIdDailyRecord::insert($chunk);
        }

        return EmployeeIdDailyRecord::where('platform_id', $platformId)
            ->where('month', $month . '-01')
            ->count();
    }

    /**
     * ===================================================================
     * المعالجة 2: الـ IDs الثابتة (التي لم يستخدمها سوى شخص واحد)
     * ===================================================================
     */
    public function resolveSingleUserIds(int $importBatchId, int $platformId, string $month): int
    {
        $singleUserCaptainIds = EmployeePlatformId::where('platform_id', $platformId)
            ->groupBy('captain_id')
            ->havingRaw('COUNT(DISTINCT employee_id) = 1')
            ->pluck('captain_id')
            ->toArray();

        if (empty($singleUserCaptainIds)) return 0;

        $singleUsers = EmployeePlatformId::where('platform_id', $platformId)
            ->whereIn('captain_id', $singleUserCaptainIds)
            ->with('employee')
            ->get()
            ->keyBy('captain_id');

        $singleUserRecords = AppDailyRecord::where('import_batch_id', $importBatchId)
            ->whereIn('captain_id', $singleUserCaptainIds)
            ->get();

        $batchMonthStart = Carbon::parse($month)->startOfMonth();
        $batchMonthEnd   = Carbon::parse($month)->endOfMonth();
        $resolvedCount = 0;

        foreach ($singleUserRecords as $record) {
            if (!isset($singleUsers[$record->captain_id]) || !$singleUsers[$record->captain_id]->employee) {
                continue;
            }

            $platformIdModel = $singleUsers[$record->captain_id];
            
            // استثناء: إذا كانت تسوية وفيها تاريخ خارج فترة الشهر المستورد
            if ($record->adjustments != 0 || $record->is_settlement || $record->suppliers_costs == 0) {
                $walletDate = $this->extractDateFromWalletNote($record->wallet_note);
                if ($walletDate) {
                    $wDate = Carbon::parse($walletDate);
                    if ($wDate->lt($batchMonthStart) || $wDate->gt($batchMonthEnd)) {
                        // خارج الفترة -> يترك غير محلول
                        continue;
                    }
                }
            }

            $record->update([
                'resolved_iqama' => $platformIdModel->employee->iqama_number,
                'employee_id'    => $platformIdModel->employee_id,
                'resolve_method' => 'single_user_id',
            ]);
            $resolvedCount++;
        }

        return $resolvedCount;
    }

    /**
     * ===================================================================
     * المعالجة 3: ربط مباشر للإيرادات (التي لم تُحل بعد)
     * ===================================================================
     */
    public function resolveRevenuesByDirectMatch(int $importBatchId, int $platformId): int
    {
        $resolved = DB::statement("
            UPDATE app_daily_records adr
            INNER JOIN employee_id_daily_records eidr
                ON adr.captain_id = eidr.captain_id
               AND DATE(adr.record_date) = eidr.work_date
               AND eidr.platform_id = ?
            INNER JOIN employees e ON eidr.employee_id = e.id
            SET
                adr.resolved_iqama = eidr.iqama_number,
                adr.employee_id    = eidr.employee_id,
                adr.resolve_method = 'direct'
            WHERE adr.import_batch_id = ?
              AND adr.resolved_iqama IS NULL
              AND adr.suppliers_costs > 0
        ", [$platformId, $importBatchId]);

        return AppDailyRecord::where('import_batch_id', $importBatchId)
            ->where('resolve_method', 'direct')
            ->count();
    }

    /**
     * ===================================================================
     * المعالجة 4: حل التسويات المتبقية (أولوية: شفت -> تاريخ المحفظة -> ربط مباشر -> تراجع تاريخي)
     * ===================================================================
     */
    public function resolveSettlements(int $importBatchId, int $platformId): array
    {
        $unresolved = AppDailyRecord::where('import_batch_id', $importBatchId)
            ->where(function ($query) {
                $query->whereNull('resolved_iqama')->orWhere('resolved_iqama', '');
            })
            ->where(function ($query) {
                $query->where('suppliers_costs', 0)->where('adjustments', '!=', 0);
            })
            ->get();

        $resolvedViaShift  = 0;
        $resolvedViaWallet = 0;
        $resolvedViaWalletFallback = 0;
        $resolvedViaDirect = 0;
        $resolvedViaFallback = 0;
        $stillUnresolved   = 0;

        foreach ($unresolved as $record) {
            $iqama = null;

            // محاولة 1: شفت
            if ($record->shift_id && $record->shift_id > 0) {
                $shiftRecord = AppDailyRecord::where('shift_id', $record->shift_id)
                    ->where('id', '!=', $record->id)
                    ->whereNotNull('resolved_iqama')
                    ->where('resolved_iqama', '!=', '')
                    ->first();

                if ($shiftRecord) {
                    $iqama = $shiftRecord->resolved_iqama;
                    $resolvedViaShift++;
                    $record->update([
                        'resolved_iqama' => $iqama,
                        'employee_id'    => $shiftRecord->employee_id,
                        'resolve_method' => 'shift_match',
                    ]);
                    continue;
                }
            }

            // محاولة 2: تاريخ المحفظة
            $walletDate = $this->extractDateFromWalletNote($record->wallet_note);
            if ($record->captain_id && $walletDate) {
                $eidr = EmployeeIdDailyRecord::where('platform_id', $platformId)
                    ->where('captain_id', $record->captain_id)
                    ->where('work_date', $walletDate)
                    ->first();

                if ($eidr) {
                    $iqama = $eidr->iqama_number;
                    $resolvedViaWallet++;
                    $record->update([
                        'resolved_iqama' => $iqama,
                        'employee_id'    => $eidr->employee_id,
                        'resolve_method' => 'wallet_date',
                    ]);
                    continue;
                }

                // NEW: Wallet Date Fallback
                // إذا كان هناك تاريخ محفظة صريح، ولكن السطر وقع في فجوة لا يوجد بها مندوب يومها
                // نقوم بالبحث للوراء انطلاقاً من "تاريخ المحفظة" لمعرفة آخر شخص سلّم المعرف قبل هذه الفجوة
                $fallbackEidr = EmployeeIdDailyRecord::where('platform_id', $platformId)
                    ->where('captain_id', $record->captain_id)
                    ->where('work_date', '<', $walletDate)
                    ->where('work_date', '>=', Carbon::parse($walletDate)->subDays(40)->format('Y-m-d'))
                    ->orderByDesc('work_date')
                    ->first();

                if ($fallbackEidr) {
                    $iqama = $fallbackEidr->iqama_number;
                    $resolvedViaWalletFallback++;
                    $record->update([
                        'resolved_iqama' => $iqama,
                        'employee_id'    => $fallbackEidr->employee_id,
                        'resolve_method' => 'wallet_fallback',
                    ]);
                    continue;
                }

                // إذا فشل التراجع من تاريخ المحفظة أيضاً، يجب التوقف وترك السطر غير محلول
                // لكي لا يتم ربطه خطأ بناءً على تاريخ السطر الحالي (لأننا متأكدون من تاريخ الحدث)
                continue; 
            }

            // محاولة 3: تطابق مباشر بناء على تاريخ السطر
            if ($record->captain_id) {
                $eidr = EmployeeIdDailyRecord::where('platform_id', $platformId)
                    ->where('captain_id', $record->captain_id)
                    ->where('work_date', date('Y-m-d', strtotime($record->record_date)))
                    ->first();

                if ($eidr) {
                    $iqama = $eidr->iqama_number;
                    $resolvedViaDirect++;
                    $record->update([
                        'resolved_iqama' => $iqama,
                        'employee_id'    => $eidr->employee_id,
                        'resolve_method' => 'direct',
                    ]);
                    continue;
                }
            }

            // محاولة 4: تراجع تاريخي
            // يتم الوصول لهذه الخطوة إذا كان السطر يقع في "فجوة" (تاريخ غير مسجل لأي مندوب).
            // المنطق الأصح هو البحث للوراء في (سجل استلام المعرفات الرسمي) لمعرفة آخر شخص استلم المعرف قبل هذا التاريخ.
            if ($record->captain_id) {
                $fallbackEidr = EmployeeIdDailyRecord::where('platform_id', $platformId)
                    ->where('captain_id', $record->captain_id)
                    ->where('work_date', '<', date('Y-m-d', strtotime($record->record_date)))
                    ->where('work_date', '>=', Carbon::parse($record->record_date)->subDays(40)->format('Y-m-d'))
                    ->orderByDesc('work_date')
                    ->first();

                if ($fallbackEidr) {
                    $iqama = $fallbackEidr->iqama_number;
                    $resolvedViaFallback++;
                    $record->update([
                        'resolved_iqama' => $iqama,
                        'employee_id'    => $fallbackEidr->employee_id,
                        'resolve_method' => 'date_fallback',
                    ]);
                    continue;
                }
            }

            if (!$iqama) {
                $stillUnresolved++;
            }
        }

        return [
            'via_shift'   => $resolvedViaShift,
            'via_wallet'  => $resolvedViaWallet,
            'via_wallet_fallback' => $resolvedViaWalletFallback,
            'via_direct'  => $resolvedViaDirect,
            'via_fallback'=> $resolvedViaFallback,
            'unresolved'  => $stillUnresolved,
        ];
    }

    private function extractDateFromWalletNote($note): ?string
    {
        if (!$note) return null;
        if (preg_match('/\b(20\d{2}[-\/]\d{1,2}[-\/]\d{1,2}|\d{1,2}[-\/]\d{1,2}[-\/]20\d{2})\b/', $note, $matches)) {
            $extractedStr = str_replace('/', '-', $matches[1]);
            try {
                return Carbon::parse($extractedStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * ===================================================================
     * تشغيل كل المعالجات دفعة واحدة بالترتيب الجديد
     * ===================================================================
     */
    public function processAll(ImportBatch $batch): array
    {
        $platformId = $batch->platform_id;
        $month      = $batch->month->format('Y-m');
        $fullMonthDate = $batch->month->format('Y-m-d'); // 2026-05-01

        // 1. توسيع الـ IDs
        $expandedCount = $this->expandIdRanges($platformId, $month);

        // 2. الـ IDs الثابتة (ربط الإيرادات والتسويات)
        $singleUserCount = $this->resolveSingleUserIds($batch->id, $platformId, $fullMonthDate);

        // 3. ربط مباشر للإيرادات (للـ IDs المتعددة)
        $revenuesDirectCount = $this->resolveRevenuesByDirectMatch($batch->id, $platformId);

        // 4. معالجة التسويات المتبقية (Shift -> Wallet -> Direct -> Fallback)
        $settlementsResult = $this->resolveSettlements($batch->id, $platformId);

        $batch->update(['status' => 'done']);

        return [
            'expanded_ids'   => $expandedCount,
            'single_users'   => $singleUserCount,
            'revenues_direct'=> $revenuesDirectCount,
            'via_shift'      => $settlementsResult['via_shift'],
            'via_wallet'     => $settlementsResult['via_wallet'],
            'via_wallet_fallback' => $settlementsResult['via_wallet_fallback'],
            'via_direct'     => $settlementsResult['via_direct'],
            'via_fallback'   => $settlementsResult['via_fallback'],
        ];
    }

    /**
     * ===================================================================
     * التراجع عن توزيع التسويات
     * ===================================================================
     */
    public function undoAdjustments(int $importBatchId): int
    {
        return AppDailyRecord::where('import_batch_id', $importBatchId)
            ->where('adjustments', '!=', 0)
            ->update([
                'resolved_iqama' => null,
                'employee_id'    => null,
                'resolve_method' => 'unresolved',
            ]);
    }

    /**
     * ===================================================================
     * المعالجة 4: استخراج الفروقات
     * ===================================================================
     */
    public function getUnresolvedRecords(int $importBatchId): \Illuminate\Database\Eloquent\Collection
    {
        return AppDailyRecord::where('import_batch_id', $importBatchId)
            ->where(function ($q) {
                $q->whereNull('resolved_iqama')
                  ->orWhere('resolved_iqama', '')
                  ->orWhere('resolve_method', 'unresolved');
            })
            ->orderBy('record_date')
            ->get();
    }

    /**
     * السطور المكررة
     */
    public function getDuplicateRecords(int $importBatchId): \Illuminate\Database\Eloquent\Collection
    {
        return AppDailyRecord::where('import_batch_id', $importBatchId)
            ->whereIn('id', function ($query) use ($importBatchId) {
                $query->select('id')
                    ->from('app_daily_records')
                    ->where('import_batch_id', $importBatchId)
                    ->whereRaw('(
                        SELECT COUNT(*) FROM app_daily_records adr2
                        WHERE adr2.captain_id = app_daily_records.captain_id
                          AND adr2.record_date = app_daily_records.record_date
                          AND adr2.import_batch_id = app_daily_records.import_batch_id
                          AND adr2.orders = app_daily_records.orders
                          AND adr2.suppliers_costs = app_daily_records.suppliers_costs
                          AND adr2.adjustments = app_daily_records.adjustments
                          AND IFNULL(adr2.shift_id, "") = IFNULL(app_daily_records.shift_id, "")
                          AND IFNULL(adr2.wallet_note, "") = IFNULL(app_daily_records.wallet_note, "")
                    ) > 1');
            })
            ->orderBy('captain_id')
            ->orderBy('record_date')
            ->get();
    }
}
