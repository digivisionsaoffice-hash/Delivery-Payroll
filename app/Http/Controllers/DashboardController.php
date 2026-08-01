<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now   = Carbon::now()->startOfMonth();
        $month = $now->format('Y-m-d');
        $prev  = $now->copy()->subMonth()->format('Y-m-d');

        // KPIs الشهر الحالي
        $currentEntries = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $month));

        $kpis = [
            'total_salary'   => $currentEntries->sum('net_salary'),
            'total_revenue'  => $currentEntries->sum('total_revenue'),
            'net_profit'     => $currentEntries->sum('profit_loss'),
            'total_orders'   => $currentEntries->sum('total_orders'),
            'active_drivers' => Employee::where('employee_status', 'active')->count(),
            'profitable'     => $currentEntries->where('profit_loss', '>', 0)->count(),
            'loss_drivers'   => $currentEntries->where('profit_loss', '<', 0)->count(),
        ];

        // مقارنة بالشهر السابق
        $prevEntries = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $prev));
        $prevSalary  = (float) $prevEntries->sum('net_salary');
        $prevRevenue = (float) $prevEntries->sum('total_revenue');
        $salaryChange  = $prevSalary  > 0 ? round((($kpis['total_salary'] - $prevSalary) / $prevSalary) * 100, 1)  : 0;
        $revenueChange = $prevRevenue > 0 ? round((($kpis['total_revenue'] - $prevRevenue) / $prevRevenue) * 100, 1) : 0;

        // بيانات مخطط الربحية الشهري (6 أشهر)
        $profitTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i)->startOfMonth();
            $revenue = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $m->format('Y-m-d')))->sum('total_revenue');
            $salary  = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('month', $m->format('Y-m-d')))->sum('net_salary');
            $profitTrend[] = [
                'month'   => $m->translatedFormat('M Y'),
                'revenue' => round($revenue, 2),
                'salary'  => round($salary, 2),
                'profit'  => round($revenue - $salary, 2),
            ];
        }

        // ربحية المنصات
        $platformProfits = Platform::withCount(['payrollPeriods as period_count'])->get()->map(function ($platform) use ($month) {
            $entries = PayrollEntry::whereHas('payrollPeriod', fn($q) => $q->where('platform_id', $platform->id)->where('month', $month));
            return [
                'name'    => $platform->name,
                'revenue' => round((float) $entries->sum('total_revenue'), 2),
                'profit'  => round((float) $entries->sum('profit_loss'), 2),
                'drivers' => $entries->count(),
            ];
        });

        // أفضل 10 سائقين أداءً
        $topDrivers = PayrollEntry::with('employee.branch')
            ->whereHas('payrollPeriod', fn($q) => $q->where('month', $month))
            ->orderByDesc('profit_loss')
            ->limit(10)
            ->get();

        // أقل سائقين ربحية (يحتاجون مراجعة)
        $lossDrivers = PayrollEntry::with('employee')
            ->whereHas('payrollPeriod', fn($q) => $q->where('month', $month))
            ->where('profit_loss', '<', 0)
            ->orderBy('profit_loss')
            ->limit(5)
            ->get();

        // آخر مسيرات الرواتب
        $recentPayrolls = PayrollPeriod::with('platform')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'kpis', 'salaryChange', 'revenueChange',
            'profitTrend', 'platformProfits',
            'topDrivers', 'lossDrivers', 'recentPayrolls',
            'month'
        ));
    }
}
