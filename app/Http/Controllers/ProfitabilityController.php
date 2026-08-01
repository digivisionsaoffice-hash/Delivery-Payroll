<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\Platform;
use Illuminate\Http\Request;

class ProfitabilityController extends Controller
{
    public function index(Request $request)
    {
        $platforms = Platform::where('is_active', true)->get();
        $months    = PayrollEntry::selectRaw('DISTINCT DATE_FORMAT(pe.month, "%Y-%m") as m')
            ->from('payroll_entries as e')
            ->join('payroll_periods as pe', 'pe.id', '=', 'e.payroll_period_id')
            ->orderByDesc('m')
            ->limit(12)
            ->pluck('m');

        $selectedMonth = $request->month ?? now()->format('Y-m');
        $selectedPlatform = $request->platform_id;

        $query = PayrollEntry::with(['employee.branch', 'payrollPeriod.platform'])
            ->whereHas('payrollPeriod', function ($q) use ($selectedMonth, $selectedPlatform) {
                $q->where('month', $selectedMonth . '-01');
                if ($selectedPlatform) $q->where('platform_id', $selectedPlatform);
            })
            ->orderBy('profit_loss');

        $entries = $query->paginate(50)->withQueryString();

        $totals = PayrollEntry::whereHas('payrollPeriod', function ($q) use ($selectedMonth, $selectedPlatform) {
            $q->where('month', $selectedMonth . '-01');
            if ($selectedPlatform) $q->where('platform_id', $selectedPlatform);
        })->selectRaw('
            SUM(profit_loss) as total_profit,
            SUM(total_revenue) as total_revenue,
            SUM(net_salary) as total_salary,
            COUNT(*) as drivers,
            SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as profitable,
            SUM(CASE WHEN profit_loss < 0 THEN 1 ELSE 0 END) as loss_count
        ')->first();

        return view('profitability.index', compact('entries', 'platforms', 'totals', 'selectedMonth', 'selectedPlatform', 'months'));
    }

    public function driver(Employee $employee)
    {
        $entries = PayrollEntry::where('employee_id', $employee->id)
            ->with('payrollPeriod.platform')
            ->orderByDesc('payroll_period_id')
            ->limit(12)
            ->get();

        return view('profitability.driver', compact('employee', 'entries'));
    }

    public function platform(Platform $platform)
    {
        $periods = $platform->payrollPeriods()->withCount('entries')
            ->orderByDesc('month')->limit(12)->get();
        return view('profitability.platform', compact('platform', 'periods'));
    }
}
