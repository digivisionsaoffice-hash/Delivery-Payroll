<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\CompanyPenalty;
use App\Models\Employee;
use App\Models\ManualMaintenance;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\Platform;
use App\Models\SparePartsMisuse;
use App\Models\TrafficViolation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_employees' => Employee::count(),
            'total_periods'   => PayrollPeriod::count(),
            'total_entries'   => PayrollEntry::count(),
            'latest_month'    => PayrollPeriod::orderByDesc('month')->value('month'),
        ];
        return view('reports.index', compact('stats'));
    }

    public function payrollReport(Request $request)
    {
        $periods = PayrollPeriod::with('platform')->orderByDesc('month')->limit(24)->get();
        return view('reports.payroll', compact('periods'));
    }

    public function profitabilityReport(Request $request)
    {
        return redirect()->route('profitability.index');
    }

    public function driversReport(Request $request)
    {
        return redirect()->route('employees.index');
    }

    public function export(string $type, Request $request)
    {
        return back()->with('error', 'هذه الميزة غير مفعلة بعد');
    }

    public function anomalies(Request $request)
    {
        $month = $request->get('month', date('Y-m'));
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $multipleIdsPerDay = \App\Models\AppDailyRecord::select('employee_id', 'record_date', DB::raw('COUNT(DISTINCT captain_id) as ids_count'))
            ->whereBetween('record_date', [$startDate, $endDate])
            ->whereNotNull('employee_id')
            ->groupBy('employee_id', 'record_date')
            ->having('ids_count', '>', 1)
            ->with('employee')
            ->get();

        foreach ($multipleIdsPerDay as $anomaly) {
            $anomaly->records = \App\Models\AppDailyRecord::where('employee_id', $anomaly->employee_id)
                ->where('record_date', $anomaly->record_date)
                ->get();
        }

        $multipleRowsPerDay = \App\Models\AppDailyRecord::select('captain_id', 'record_date', DB::raw('COUNT(*) as rows_count'))
            ->whereBetween('record_date', [$startDate, $endDate])
            ->groupBy('captain_id', 'record_date')
            ->having('rows_count', '>', 1)
            ->get();

        foreach ($multipleRowsPerDay as $anomaly) {
            $anomaly->records = \App\Models\AppDailyRecord::where('captain_id', $anomaly->captain_id)
                ->where('record_date', $anomaly->record_date)
                ->with('employee')
                ->get();
        }

        return view('reports.anomalies', compact('multipleIdsPerDay', 'multipleRowsPerDay', 'month'));
    }

    public function performance(Request $request)
    {
        $platforms        = Platform::orderBy('name')->get();
        $selectedPlatform = $request->get('platform_id');
        $selectedMonth    = $request->get('month', date('Y-m'));
        $monthDate        = $selectedMonth . '-01';
        $prevMonthDate    = Carbon::parse($monthDate)->subMonth()->format('Y-m-01');

        $currentEntries = PayrollEntry::whereHas('payrollPeriod', function ($q) use ($monthDate, $selectedPlatform) {
            $q->where('month', $monthDate);
            if ($selectedPlatform) $q->where('platform_id', $selectedPlatform);
        })->with('employee')->get();

        $prevEntries = PayrollEntry::whereHas('payrollPeriod', function ($q) use ($prevMonthDate, $selectedPlatform) {
            $q->where('month', $prevMonthDate);
            if ($selectedPlatform) $q->where('platform_id', $selectedPlatform);
        })->get()->keyBy('employee_id');

        $comparison = $currentEntries->map(function ($entry) use ($prevEntries) {
            $prev = $prevEntries->get($entry->employee_id);
            return [
                'entry'          => $entry,
                'prev_revenue'   => $prev?->total_revenue ?? 0,
                'prev_orders'    => $prev?->total_orders ?? 0,
                'prev_days'      => $prev?->working_days ?? 0,
                'revenue_change' => $prev && $prev->total_revenue > 0
                    ? round((($entry->total_revenue - $prev->total_revenue) / $prev->total_revenue) * 100, 1)
                    : null,
                'orders_change'  => $prev && $prev->total_orders > 0
                    ? round((($entry->total_orders - $prev->total_orders) / $prev->total_orders) * 100, 1)
                    : null,
            ];
        })->sortByDesc(fn($r) => $r['entry']->total_revenue)->values();

        $summary = [
            'current_revenue' => $currentEntries->sum('total_revenue'),
            'current_salary'  => $currentEntries->sum('total_salary'),
            'current_orders'  => $currentEntries->sum('total_orders'),
            'current_count'   => $currentEntries->count(),
            'prev_revenue'    => $prevEntries->sum('total_revenue'),
            'prev_salary'     => $prevEntries->sum('total_salary'),
            'prev_orders'     => $prevEntries->sum('total_orders'),
            'prev_count'      => $prevEntries->count(),
        ];

        $trendData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $loopMonth = Carbon::parse($monthDate)->subMonths($i)->format('Y-m-01');
            $periodEntries = PayrollEntry::whereHas('payrollPeriod', function ($q) use ($loopMonth, $selectedPlatform) {
                $q->where('month', $loopMonth);
                if ($selectedPlatform) $q->where('platform_id', $selectedPlatform);
            })->get();

            $trendData->push([
                'month'   => Carbon::parse($loopMonth)->locale('ar')->translatedFormat('M Y'),
                'revenue' => round($periodEntries->sum('total_revenue')),
                'salary'  => round($periodEntries->sum('total_salary')),
                'profit'  => round($periodEntries->sum('profit_loss')),
                'drivers' => $periodEntries->count(),
            ]);
        }

        return view('reports.performance', compact(
            'platforms', 'selectedPlatform', 'selectedMonth', 'comparison', 'summary', 'trendData'
        ));
    }

    public function deductionsSummary(Request $request)
    {
        $selectedMonth = $request->get('month', date('Y-m'));
        $monthDate     = $selectedMonth . '-01';

        $advances    = Advance::where('payroll_month', $monthDate)->with('employee')->get();
        $violations  = TrafficViolation::where('payroll_month', $monthDate)->with('employee')->get();
        $spareParts  = SparePartsMisuse::where('payroll_month', $monthDate)->with('employee')->get();
        $maintenance = ManualMaintenance::where('payroll_month', $monthDate)->with('employee')->get();
        $penalties   = CompanyPenalty::where('payroll_month', $monthDate)->with('employee')->get();

        $summary = [
            'advances'    => (float) $advances->sum('amount'),
            'violations'  => (float) $violations->sum('amount'),
            'spare_parts' => (float) $spareParts->sum('total_value'),
            'maintenance' => (float) $maintenance->sum('discount_amount'),
            'penalties'   => (float) $penalties->sum('discount_amount'),
        ];
        $summary['total'] = array_sum($summary);

        $allEmpIds = array_unique(array_merge(
            $advances->pluck('employee_id')->toArray(),
            $violations->pluck('employee_id')->toArray(),
            $spareParts->pluck('employee_id')->toArray(),
            $maintenance->pluck('employee_id')->toArray(),
            $penalties->pluck('employee_id')->toArray(),
        ));

        $byEmployee = collect();
        foreach ($allEmpIds as $empId) {
            $emp = Employee::find($empId);
            if (!$emp) continue;

            $totalDed = $advances->where('employee_id', $empId)->sum('amount')
                      + $violations->where('employee_id', $empId)->sum('amount')
                      + $spareParts->where('employee_id', $empId)->sum('total_value')
                      + $maintenance->where('employee_id', $empId)->sum('discount_amount')
                      + $penalties->where('employee_id', $empId)->sum('discount_amount');

            $payrollEntry = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $monthDate))
                ->where('employee_id', $empId)->first();
            $salaryRatio = $payrollEntry && $payrollEntry->total_salary > 0
                ? round(($totalDed / $payrollEntry->total_salary) * 100, 1)
                : null;

            $byEmployee->push([
                'employee'     => $emp,
                'total'        => (float) $totalDed,
                'salary_ratio' => $salaryRatio,
                'advances'     => (float) $advances->where('employee_id', $empId)->sum('amount'),
                'violations'   => (float) $violations->where('employee_id', $empId)->sum('amount'),
                'spare_parts'  => (float) $spareParts->where('employee_id', $empId)->sum('total_value'),
                'maintenance'  => (float) $maintenance->where('employee_id', $empId)->sum('discount_amount'),
                'penalties'    => (float) $penalties->where('employee_id', $empId)->sum('discount_amount'),
            ]);
        }

        $byEmployee = $byEmployee->sortByDesc('total')->values();

        return view('reports.deductions', compact('selectedMonth', 'summary', 'byEmployee'));
    }

    public function inactiveDrivers(Request $request)
    {
        $platforms        = Platform::orderBy('name')->get();
        $selectedMonth    = $request->get('month', date('Y-m'));
        $selectedPlatform = $request->get('platform_id');
        $monthDate        = $selectedMonth . '-01';
        $prevMonthDate    = Carbon::parse($monthDate)->subMonth()->format('Y-m-01');

        $prevEmpIds = PayrollEntry::whereHas('payrollPeriod', function ($q) use ($prevMonthDate, $selectedPlatform) {
            $q->where('month', $prevMonthDate);
            if ($selectedPlatform) $q->where('platform_id', $selectedPlatform);
        })->where('total_orders', '>', 0)->pluck('employee_id')->toArray();

        $currentEmpIds = PayrollEntry::whereHas('payrollPeriod', function ($q) use ($monthDate, $selectedPlatform) {
            $q->where('month', $monthDate);
            if ($selectedPlatform) $q->where('platform_id', $selectedPlatform);
        })->where('total_orders', '>', 0)->pluck('employee_id')->toArray();

        $stoppedEmpIds = array_diff($prevEmpIds, $currentEmpIds);

        $stoppedDrivers = Employee::whereIn('id', $stoppedEmpIds)->get()
            ->map(function ($emp) use ($prevMonthDate) {
                $prevEntry = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $prevMonthDate))
                    ->where('employee_id', $emp->id)->first();
                return [
                    'employee'     => $emp,
                    'prev_orders'  => $prevEntry?->total_orders ?? 0,
                    'prev_revenue' => $prevEntry?->total_revenue ?? 0,
                    'prev_days'    => $prevEntry?->working_days ?? 0,
                ];
            })->sortByDesc('prev_orders')->values();

        $lowActivityDrivers = collect();
        foreach ($currentEmpIds as $empId) {
            if (in_array($empId, $prevEmpIds)) {
                $curr = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $monthDate))->where('employee_id', $empId)->first();
                $prev = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $prevMonthDate))->where('employee_id', $empId)->first();
                if ($curr && $prev && $prev->total_orders > 0) {
                    $drop = (($prev->total_orders - $curr->total_orders) / $prev->total_orders) * 100;
                    if ($drop >= 40) {
                        $emp = Employee::find($empId);
                        $lowActivityDrivers->push([
                            'employee'     => $emp,
                            'curr_orders'  => $curr->total_orders,
                            'prev_orders'  => $prev->total_orders,
                            'drop_pct'     => round($drop, 1),
                            'curr_revenue' => $curr->total_revenue,
                            'prev_revenue' => $prev->total_revenue,
                        ]);
                    }
                }
            }
        }
        $lowActivityDrivers = $lowActivityDrivers->sortByDesc('drop_pct')->values();

        return view('reports.inactive', compact(
            'platforms', 'selectedMonth', 'selectedPlatform',
            'stoppedDrivers', 'lowActivityDrivers'
        ));
    }
}
