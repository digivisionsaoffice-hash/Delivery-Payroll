<?php

namespace App\Services;

use App\Models\AppDailyRecord;
use App\Models\Advance;
use App\Models\CompanyPenalty;
use App\Models\Employee;
use App\Models\EmployeeMonthlyExpense;
use App\Models\ManualMaintenance;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\Platform;
use App\Models\PlatformSettings;
use App\Models\PreSalaryPayment;
use App\Models\SparePartsMisuse;
use App\Models\TrafficViolation;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * احتساب ايام العمل الفعلية
     * يوم عمل = إيراد > 0 + ساعات عمل >= الحد في الضبط
     * يستثني التسويات (سطور adjustments فقط بدون إيراد)
     */
    public function calculateWorkingDays(int $employeeId, string $month, PlatformSettings $settings): int
    {
        if ($settings->isKeetaSlabs()) {
            // تقرير كيتا مجمع (سطر واحد للمعرف) ولا يمكن حساب أيام العمل منه
            return 0;
        }

        $periodStart = date('Y-m-01', strtotime($month));
        $periodEnd   = date('Y-m-t', strtotime($month));

        return AppDailyRecord::where('employee_id', $employeeId)
            ->where('platform_id', $settings->platform_id)
            ->whereBetween('record_date', [$periodStart, $periodEnd])
            ->where('net_cost', '>', 0)
            ->where('working_hours', '>=', $settings->min_working_hours_per_day)
            ->select(DB::raw('COUNT(DISTINCT DATE(record_date)) as days'))
            ->value('days') ?? 0;
    }

    /**
     * احتساب إجمالي الطلبات
     */
    public function calculateTotalOrders(int $employeeId, string $month, ?PlatformSettings $settings = null): int
    {
        $periodStart = date('Y-m-01', strtotime($month));
        $periodEnd   = date('Y-m-t', strtotime($month));

        $query = AppDailyRecord::where('employee_id', $employeeId)
            ->whereBetween('record_date', [$periodStart, $periodEnd]);
            
        if ($settings) {
            $query->where('platform_id', $settings->platform_id);
        }

        return $query->sum('orders') ?? 0;
    }

    /**
     * احتساب الطلبات التي تجاوزت التارجت اليومي
     */
    public function calculateDailyTargetExcess(int $employeeId, string $month, int $dailyTarget, float $minHours = 0, ?PlatformSettings $settings = null): int
    {
        if ($dailyTarget <= 0) return 0;

        $periodStart = date('Y-m-01', strtotime($month));
        $periodEnd   = date('Y-m-t', strtotime($month));

        $query = AppDailyRecord::where('employee_id', $employeeId)
            ->whereBetween('record_date', [$periodStart, $periodEnd]);
            
        if ($settings) {
            $query->where('platform_id', $settings->platform_id);
        }

        $dailyOrders = $query->groupBy(DB::raw('DATE(record_date)'))
            ->select(
                DB::raw('DATE(record_date) as date'),
                DB::raw('SUM(orders) as day_orders'),
                DB::raw('SUM(working_hours) as day_hours')
            )->get();

        $totalExcess = 0;
        foreach ($dailyOrders as $day) {
            if ($day->day_hours >= $minHours && $day->day_orders > $dailyTarget) {
                $totalExcess += $day->day_orders - $dailyTarget;
            }
        }

        return $totalExcess;
    }

    public function calculateTotalRevenue(int $employeeId, string $month, PlatformSettings $settings): float
    {
        $periodStart = date('Y-m-01', strtotime($month));
        $periodEnd   = date('Y-m-t', strtotime($month));

        if ($settings->isKeetaSlabs()) {
            return (float) AppDailyRecord::where('employee_id', $employeeId)
                ->where('platform_id', $settings->platform_id)
                ->whereBetween('record_date', [$periodStart, $periodEnd])
                ->sum('total_dues');
        }

        return (float) AppDailyRecord::where('employee_id', $employeeId)
            ->where('platform_id', $settings->platform_id)
            ->whereBetween('record_date', [$periodStart, $periodEnd])
            ->sum('net_cost');
    }

    /**
     * ===================================================================
     * احتساب الراتب الأساسي - نظام الراتب الثابت المشروط
     * ===================================================================
     */
    public function calculateFixedBasicSalary(
        float $agreedSalary, 
        int $workingDays, 
        int $targetDays = 26, 
        string $absenceType = 'worked_days_only',
        float $absenceRate = 1.0, 
        float $extraRate = 1.0
    ): float {
        $dailyRate = $agreedSalary / 30;

        if ($absenceType === 'pure_daily') {
            return round($dailyRate * $workingDays, 2);
        }

        if ($workingDays > $targetDays) {
            $extraDays = $workingDays - $targetDays;
            return round($agreedSalary + ($dailyRate * $extraDays * $extraRate), 2);
        } elseif ($workingDays < $targetDays) {
            if ($absenceType === 'standard_deduction') {
                $absenceDays = $targetDays - $workingDays;
                $penalty = $dailyRate * $absenceDays * $absenceRate;
                return round(max(0, $agreedSalary - $penalty), 2);
            } else {
                // worked_days_only OR strict_daily_unless_exceeded
                return round($dailyRate * $workingDays, 2);
            }
        } else {
            // $workingDays == $targetDays
            if ($absenceType === 'strict_daily_unless_exceeded') {
                return round($dailyRate * $workingDays, 2);
            }
            return round($agreedSalary, 2);
        }
    }

    /**
     * ===================================================================
     * احتساب البونص اليومي (فوق التارجت اليومي)
     * ===================================================================
     * الزيادة المجمعة × قيمة الحافز من الضبط
     */
    public function calculateDailyBonus(int $dailyTargetExcess, float $bonusPerOrder): float
    {
        return round($dailyTargetExcess * $bonusPerOrder, 2);
    }

    /**
     * ===================================================================
     * احتساب العمولة بنظام الشرائح
     * ===================================================================
     * الشرائح العادية + الزيادة فوق التارجت الشهري × قيمة الحافز
     * مثال: 600 طلب، تارجت 450
     *   - شرائح 0→450 = X ريال
     *   - زيادة: (600-450) × bonus_rate
     */
    public function calculateTieredCommission(int $totalOrders, PlatformSettings $settings): float
    {
        $tiers = $settings->commission_tiers ?? [];
        if (empty($tiers)) return 0;

        $basePay = 0;
        foreach ($tiers as $tier) {
            $from = $tier['from'] ?? 0;
            $to   = $tier['to'] ?? null;
            $rate = $tier['rate'] ?? 0;

            if ($totalOrders <= $from) break;

            $ceiling      = $to !== null ? min($totalOrders, $to) : $totalOrders;
            $ordersInTier = $ceiling - $from;

            if ($ordersInTier > 0) {
                $basePay += $ordersInTier * $rate;
            }
        }

        // الزيادة فوق التارجت الشهري
        $monthlyBonus = 0;
        if ($settings->monthly_target > 0 && $totalOrders > $settings->monthly_target) {
            $excess       = $totalOrders - $settings->monthly_target;
            $monthlyBonus = $excess * $settings->bonus_per_excess_order;
        }

        return round($basePay + $monthlyBonus, 2);
    }

    /**
     * ===================================================================
     * احتساب كيتا شرايح
     * ===================================================================
     * الحسبة:
     * 1. شرائح أساسية (0→Tier1→Tier2→Tier3 بالطلب)
     * 2. إذا تجاوز base_min_orders: راتب ثابت أو 8×طلب
     * 3. حافز درجة (Grade) بناءً على إجمالي الطلبات
     * 4. بونص إضافي ثابت إذا تجاوز bonus_min_orders
     */
    public function calculateKeetaSlabs(int $totalOrders, float $trialIncentives, float $capacityIncentives, float $agreedSalary, string $city, PlatformSettings $settings): array
    {
        $cfg = $settings->keeta_slabs_config;
        if (!$cfg) return ['basePay' => 0, 'gradeIncentive' => 0, 'bonus' => 0, 'gradeName' => 'D'];

        $tiers          = $cfg['tiers']          ?? [];
        $grades         = $cfg['grades']          ?? [];
        $baseMinOrders  = (int)   ($cfg['base_min_orders']  ?? 450);
        $perOrderRate   = (float) ($cfg['per_order_rate']   ?? 8);
        $bonusMinOrders = (int)   ($cfg['bonus_min_orders'] ?? 0);
        $bonusValue     = (float) ($cfg['bonus_value']      ?? 0);

        // مجموع الحوافز لتحديد الدرجة
        $totalIncentives = $trialIncentives + $capacityIncentives;

        // --- تحديد الدرجة من إعدادات المنصة ديناميكياً ---
        $matchedGrade  = null;
        $gradeName     = 'D';
        $gradeIndex    = -1;
        $isPunishment  = false;

        foreach ($grades as $idx => $grade) {
            $min = (float) ($grade['min'] ?? 0);
            $max = isset($grade['max']) && $grade['max'] !== null && $grade['max'] !== '' ? (float) $grade['max'] : PHP_FLOAT_MAX;

            if ($totalIncentives >= $min && $totalIncentives <= $max) {
                $matchedGrade = $grade;
                $gradeName    = chr(65 + $idx); // 0=>A, 1=>B, 2=>C, 3=>D ...
                $gradeIndex   = $idx;
                $isPunishment = (bool) ($grade['is_punishment'] ?? false);
                break;
            }
        }

        // --- قراءة قيمة الحافز للمدينة من النص (ديناميكياً) ---
        $incentiveRate = $this->resolveIncentiveForCity($matchedGrade['incentive'] ?? '0', $city);

        // --- قريد عقوبة: يلغي الراتب الثابت تماماً → طلبات × الحافز ---
        if ($isPunishment) {
            return [
                'basePay'        => round($totalOrders * $incentiveRate, 2),
                'gradeIncentive' => 0,
                'bonus'          => 0,
                'gradeName'      => $gradeName,
            ];
        }

        // --- 1. حساب شرائح العمولة الأساسية (ما دون الحد الأدنى) ---
        $tieredPay = 0;
        if ($totalOrders < $baseMinOrders) {
            foreach ($tiers as $tier) {
                $from = (int) ($tier['from'] ?? 0);
                $to   = isset($tier['to']) && $tier['to'] !== null && $tier['to'] > 0 ? (int) $tier['to'] : PHP_INT_MAX;
                $rate = (float) ($tier['rate'] ?? 0);
                if ($totalOrders >= $from && $totalOrders <= $to) {
                    $tieredPay = $totalOrders * $rate;
                    break;
                }
            }
        }

        // --- 2. ما فوق الحد الأدنى: راتب ثابت أو بالطلب ---
        $basePay = 0;
        if ($totalOrders >= $baseMinOrders) {
            if ($agreedSalary > 0 && $agreedSalary < 1000) {
                $basePay = $baseMinOrders * $agreedSalary;
            } elseif ($agreedSalary >= 1000) {
                $basePay = $agreedSalary;
            } else {
                $basePay = (float) ($cfg['base_salary_value'] ?? 2500);
            }
        }

        // --- 3. حافز الدرجة (Grade Incentive) → ما زاد عن الحد الأدنى ---
        $gradeIncentive = 0;
        if ($matchedGrade && $totalOrders > $baseMinOrders) {
            $excessOrders   = $totalOrders - $baseMinOrders;
            $gradeIncentive = $excessOrders * $incentiveRate;
        }

        // --- 4. بونص إضافي ثابت (Grade A فقط → أول درجة في المصفوفة) ---
        $bonus = 0;
        if ($gradeIndex === 0 && $bonusMinOrders > 0 && $totalOrders >= $bonusMinOrders) {
            $bonus = $bonusValue;
        }

        return [
            'basePay'        => round($tieredPay + $basePay, 2),
            'gradeIncentive' => round($gradeIncentive, 2),
            'bonus'          => round($bonus, 2),
            'gradeName'      => $gradeName,
        ];
    }

    /**
     * يقرأ نص الحافز (مثل "جدة:7, الطائف:6, الافتراضي:5") ويعيد قيمة المدينة.
     * يدعم أيضاً رقماً مجرداً مثل "5" أو "7.5".
     */
    private function resolveIncentiveForCity(string $incentiveText, string $city): float
    {
        $incentiveText = trim($incentiveText);

        // إذا كان رقماً مجرداً
        if (is_numeric($incentiveText)) {
            return (float) $incentiveText;
        }

        // تحليل النص: "جدة:7, الطائف:6, الافتراضي:5"
        $entries  = array_map('trim', explode(',', $incentiveText));
        $default  = null;
        $cityLower = mb_strtolower($city);

        foreach ($entries as $entry) {
            if (!str_contains($entry, ':')) continue;
            [$key, $val] = array_map('trim', explode(':', $entry, 2));
            $keyLower = mb_strtolower($key);

            if ($keyLower === 'الافتراضي' || $keyLower === 'default') {
                $default = (float) $val;
                continue;
            }
            // مطابقة جزئية للمدينة (مثلاً "جدة" تطابق "Jeddah" أو "جدة الشمال")
            if (mb_strpos($cityLower, $keyLower) !== false || mb_strpos(mb_strtolower($key), $cityLower) !== false) {
                return (float) $val;
            }
        }

        return $default ?? 0.0;
    }

    /**
     * تسويات التطبيق (القيم السالبة في adjustments)
     */

    public function calculateAppSettlements(int $employeeId, string $month, ?PlatformSettings $settings = null): float
    {
        $periodStart = date('Y-m-01', strtotime($month));
        $periodEnd   = date('Y-m-t', strtotime($month));

        if ($settings && $settings->isKeetaSlabs()) {
            $row = AppDailyRecord::where('employee_id', $employeeId)
                ->where('platform_id', $settings->platform_id)
                ->whereBetween('record_date', [$periodStart, $periodEnd])
                ->selectRaw('SUM(suppliers_costs) as deductions, SUM(food_damage) as food_damage, SUM(tga_discount) as tga_discount, SUM(adjustments) as adjustments')
                ->first();
                
            $totalAppSettlements = (float) ($row->deductions ?? 0) + (float) ($row->food_damage ?? 0) + (float) ($row->tga_discount ?? 0) + (float) ($row->adjustments ?? 0);
            return $totalAppSettlements < 0 ? round(abs($totalAppSettlements), 2) : 0;
        }

        $query = AppDailyRecord::where('employee_id', $employeeId)
            ->whereBetween('record_date', [$periodStart, $periodEnd]);
            
        if ($settings) {
            $query->where('platform_id', $settings->platform_id);
        }
        
        $settlements = $query->sum('adjustments');

        // إذا كان المجموع بالسالب فهو خصم، وإذا كان موجباً فهو تعويض يلغي الخصم
        return $settlements < 0 ? round(abs((float) $settlements), 2) : 0;
    }

    /**
     * تجميع كل الخصومات
     */
    public function sumAllDeductions(int $employeeId, string $month, bool $isCommissionSystem, ?PlatformSettings $settings = null): array
    {
        $advances     = (float) Advance::where('employee_id', $employeeId)->where('payroll_month', $month)->sum('amount');
        $violations   = (float) TrafficViolation::where('employee_id', $employeeId)->where('payroll_month', $month)->sum('amount');
        $spareParts   = (float) SparePartsMisuse::where('employee_id', $employeeId)->where('payroll_month', $month)->sum('total_value');
        $maintenance  = (float) ManualMaintenance::where('employee_id', $employeeId)->where('payroll_month', $month)->sum('discount_amount');
        $penalties    = (float) CompanyPenalty::where('employee_id', $employeeId)->where('payroll_month', $month)->sum('discount_amount');
        $appSettlements = $this->calculateAppSettlements($employeeId, $month, $settings);

        $fuel = $housing = $packages = $consumableMaintenance = 0;

        if ($isCommissionSystem) {
            $expense = EmployeeMonthlyExpense::where('employee_id', $employeeId)
                ->where('payroll_month', $month)
                ->first();

            if ($expense) {
                $fuel                = (float) $expense->fuel;
                $housing             = (float) $expense->housing;
                $packages            = (float) $expense->packages;
                $consumableMaintenance = (float) $expense->consumable_maintenance;
            }
        }

        $total = $appSettlements + $advances + $violations + $spareParts
               + $maintenance + $penalties + $fuel + $housing + $packages
               + $consumableMaintenance;

        return compact(
            'appSettlements', 'advances', 'violations', 'spareParts',
            'maintenance', 'penalties', 'fuel', 'housing', 'packages',
            'consumableMaintenance', 'total'
        );
    }

    /**
     * ===================================================================
     * الحساب الكامل لمسير راتب موظف واحد
     * ===================================================================
     */
    public function calculateEntry(Employee $employee, PayrollPeriod $period, PlatformSettings $settings): array
    {
        $month              = $period->month->format('Y-m-01');
        $isCommission       = $employee->isCommissionSystem();
        $minHours           = $settings->min_working_hours_per_day;
        $finalGrade         = null;

        // تحديد المدينة مسبقاً لاستخدامها في حسابات كيتا
        $appCity = AppDailyRecord::where('employee_id', $employee->id)
            ->where('platform_id', $settings->platform_id)
            ->whereBetween('record_date', [$month, date('Y-m-t', strtotime($month))])
            ->whereNotNull('branch_name')
            ->where('branch_name', '!=', '')
            ->value('branch_name');
        
        $finalCity = $appCity ?: $employee->city;

        // أداء الشهر
        $workingDays        = $this->calculateWorkingDays($employee->id, $month, $settings);
        $totalOrders        = $this->calculateTotalOrders($employee->id, $month, $settings);
        
        // احتساب التارجت (هل نرتبط بساعات العمل أم لا؟)
        $targetMinHours     = $settings->link_target_to_hours ? $minHours : 0;
        $dailyTargetExcess  = $this->calculateDailyTargetExcess($employee->id, $month, $settings->daily_target, $targetMinHours, $settings);
        
        $totalRevenue       = $this->calculateTotalRevenue($employee->id, $month, $settings);

        // الراتب
        $agreedSalary = 0;
        if ($isCommission) {
            $basicSalary = $this->calculateTieredCommission($totalOrders, $settings);
            $bonus       = 0; // البونص مدمج في الشرائح
        } elseif ($settings->isKeetaSlabs()) {
            $basicSalary = 0;
            $bonus       = 0;
            $agreedSalary = $employee->agreed_salary > 0 ? $employee->agreed_salary : 0;
            
            // حساب كيتا شرائح يتم بشكل منفصل لكل معرّف (ID) ثم يجمع الناتج كما طلب العميل
            $captainIds = AppDailyRecord::where('employee_id', $employee->id)
                ->where('platform_id', $settings->platform_id)
                ->whereBetween('record_date', [$month, date('Y-m-t', strtotime($month))])
                ->whereNotNull('captain_id')
                ->where('captain_id', '!=', '')
                ->distinct()
                ->pluck('captain_id');
                
            $gradesArray = [];
            foreach ($captainIds as $capId) {
                $capOrders = (int) AppDailyRecord::where('employee_id', $employee->id)
                    ->where('platform_id', $settings->platform_id)
                    ->where('captain_id', $capId)
                    ->whereBetween('record_date', [$month, date('Y-m-t', strtotime($month))])
                    ->sum('orders');
                    
                $capTrial = (float) AppDailyRecord::where('employee_id', $employee->id)
                    ->where('platform_id', $settings->platform_id)
                    ->where('captain_id', $capId)
                    ->whereBetween('record_date', [$month, date('Y-m-t', strtotime($month))])
                    ->sum('bonus_trial');
                    
                $capCapacity = (float) AppDailyRecord::where('employee_id', $employee->id)
                    ->where('platform_id', $settings->platform_id)
                    ->where('captain_id', $capId)
                    ->whereBetween('record_date', [$month, date('Y-m-t', strtotime($month))])
                    ->sum('bonus_capacity');
                    
                $slabs = $this->calculateKeetaSlabs($capOrders, $capTrial, $capCapacity, (float) $employee->agreed_salary, $finalCity ?? '', $settings);
                $basicSalary += $slabs['basePay'];
                $dailyTargetExcess += $slabs['gradeIncentive'];
                $bonus += $slabs['bonus'];
                if (!empty($slabs['gradeName'])) {
                    $gradesArray[] = $slabs['gradeName'];
                }
            }
            $finalGrade = implode(', ', array_unique($gradesArray));
        } else {
            // الأولوية لبيانات الموظف: إذا كان راتبه المسجل > 0 نأخذه، وإلا نأخذ من إعدادات المنصة
            $profileSalary = $employee->agreed_salary > 0 ? $employee->agreed_salary : $settings->basic_salary;
            
            // إذا كان الموظف لديه أيام عمل أو طلبات أو إيراد (يعني موجود في كشف العمل أو التغير) يأخذ راتبه
            // أما إذا كان كل شيء صفر (نزل فقط بسبب تسوية مالية)، يكون الراتب المتفق صفر
            if ($workingDays > 0 || $totalOrders > 0 || $totalRevenue > 0) {
                $agreedSalary = $profileSalary;
            } else {
                $agreedSalary = 0;
            }

            $targetDays   = $settings->target_working_days ?? 26;
            $absenceType  = $settings->absence_deduction_type ?? 'worked_days_only';
            $absenceRate  = $settings->absence_deduction_rate ?? 1.0;
            $extraRate    = $settings->extra_day_bonus_rate ?? 1.0;
            $basicSalary = $this->calculateFixedBasicSalary($agreedSalary, $workingDays, $targetDays, $absenceType, $absenceRate, $extraRate);
            $bonus       = $this->calculateDailyBonus($dailyTargetExcess, $settings->bonus_per_excess_order);
        }

        if ($settings->isKeetaSlabs()) {
            $totalSalary = round($basicSalary + $bonus + $dailyTargetExcess, 2);
        } else {
            $totalSalary = round($basicSalary + $bonus, 2);
        }

        // الخصومات
        $deductions         = $this->sumAllDeductions($employee->id, $month, $isCommission, $settings);
        $totalDeductions    = $deductions['total'];

        // الصافي
        $netSalary          = round($totalSalary - $totalDeductions, 2);

        // المدد
        $preSalaryPaid      = (float) PreSalaryPayment::where('employee_id', $employee->id)
                                ->where('payroll_month', $month)->sum('amount');
        $remainingSalary    = round($netSalary - $preSalaryPaid, 2);

        // الربحية: الايراد - اجمالي الراتب (حسب طلب العميل)
        $totalDriverCost    = $totalSalary;
        $profitLoss         = round($totalRevenue - $totalSalary, 2);

        // IDs التي عمل بها
        $idNumbersArray = AppDailyRecord::where('employee_id', $employee->id)
            ->where('platform_id', $settings->platform_id)
            ->whereBetween('record_date', [$month, date('Y-m-t', strtotime($month))])
            ->whereNotNull('captain_id')
            ->where('captain_id', '!=', '')
            ->distinct()
            ->pluck('captain_id')
            ->toArray();
        $idNumbers = implode(', ', $idNumbersArray);

        // المدينة تم حسابها في بداية الدالة
        
        return [
            'payroll_period_id'      => $period->id,
            'employee_id'            => $employee->id,
            'iqama_number'           => $employee->iqama_number,
            'total_orders'           => $totalOrders,
            'working_days'           => $workingDays,
            'daily_target_excess'    => $dailyTargetExcess,
            'total_revenue'          => $totalRevenue,
            'agreed_salary'          => $agreedSalary,
            'basic_salary'           => $basicSalary,
            'bonus'                  => $bonus,
            'total_salary'           => $totalSalary,
            'app_settlements'        => $deductions['appSettlements'],
            'advances'               => $deductions['advances'],
            'traffic_violations'     => $deductions['violations'],
            'spare_parts'            => $deductions['spareParts'],
            'maintenance'            => $deductions['maintenance'],
            'company_discount'       => $deductions['penalties'],
            'fuel'                   => $deductions['fuel'],
            'housing'                => $deductions['housing'],
            'packages'               => $deductions['packages'],
            'consumable_maintenance' => $deductions['consumableMaintenance'],

            'total_deductions'       => $totalDeductions,
            'net_salary'             => $netSalary,
            'pre_salary_paid'        => $preSalaryPaid,
            'remaining_salary'       => $remainingSalary,
            'total_driver_cost'      => $totalDriverCost,
            'profit_loss'            => round($totalRevenue - $totalDriverCost, 2),
            'contract_type'          => $employee->contract_type,
            'salary_system'          => $employee->salary_system,
            'application_name'       => $period->platform->name ?? '',
            'branch'                 => $employee->branch?->name ?? '',
            'city'                   => $finalCity,
            'id_numbers'             => $idNumbers,
            'grade'                  => $finalGrade,
        ];
    }

    /**
     * احتساب مسير الرواتب الكامل لشهر ومنصة
     */
    public function calculateFullPayroll(PayrollPeriod $period): int
    {
        $platform = $period->platform;
        $month    = $period->month->format('Y-m-01');
        $settings = $platform->settingsForMonth($month);

        if (!$settings) {
            throw new \Exception("لم يتم تحديد إعدادات الضبط لهذا الشهر والمنصة");
        }

        // تم إزالة منع الاحتساب في حال وجود سجلات غير محلولة بناءً على طلب العميل
        // النظام سيقوم بالاحتساب متجاهلاً التسويات أو الطلبات غير المخصصة

        // 1. الموظفون النشطون المرتبطون بهذه المنصة (عبر IDs) ولديهم إيرادات أو تسويات
        $appRecordEmpIds = AppDailyRecord::where('platform_id', $period->platform_id)
            ->whereBetween('record_date', [
                date('Y-m-01', strtotime($month)),
                date('Y-m-t', strtotime($month)),
            ])
            ->whereNotNull('employee_id')
            ->distinct()
            ->pluck('employee_id')
            ->toArray();

        // دمج المصفوفتين لضمان نزول أي موظف له ارتباط مالي بالشهر (تم الإلغاء بناءً على طلب العميل ليقتصر على كشف التطبيق فقط)
        $employeeIds = $appRecordEmpIds;

        $count = 0;
        DB::transaction(function () use ($employeeIds, $period, $settings, &$count) {
            foreach ($employeeIds as $empId) {
                $employee = Employee::find($empId);
                if (!$employee) continue;

                $entryData = $this->calculateEntry($employee, $period, $settings);

                PayrollEntry::updateOrCreate(
                    ['payroll_period_id' => $period->id, 'employee_id' => $empId],
                    $entryData
                );
                $count++;
            }
        });

        $period->update(['status' => 'calculated']);

        return $count;
    }
}
