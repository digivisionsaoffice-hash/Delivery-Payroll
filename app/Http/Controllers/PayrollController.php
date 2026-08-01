<?php

namespace App\Http\Controllers;

use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\Platform;
use App\Services\PayrollCalculationService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    public function __construct(private PayrollCalculationService $calculator) {}

    public function index()
    {
        $periods = PayrollPeriod::with('platform', 'approver')
            ->withCount('entries')
            ->orderByDesc('month')
            ->paginate(20);

        return view('payroll.index', compact('periods'));
    }

    public function create()
    {
        $platforms = Platform::where('is_active', true)->get();
        return view('payroll.create', compact('platforms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'month'       => 'required|date_format:Y-m',
            'notes'       => 'nullable|string',
        ]);

        $period = PayrollPeriod::firstOrCreate(
            ['platform_id' => $data['platform_id'], 'month' => $data['month'] . '-01'],
            ['status' => 'draft', 'notes' => $data['notes'] ?? null]
        );

        return redirect()->route('payroll.show', $period)
            ->with('success', 'تم إنشاء دورة الرواتب');
    }

    public function show(PayrollPeriod $payroll)
    {
        $payroll->load('platform', 'approver');
        $entries = PayrollEntry::where('payroll_period_id', $payroll->id)
            ->with('employee.branch')
            ->orderByDesc('profit_loss')
            ->paginate(50);

        $totals = PayrollEntry::where('payroll_period_id', $payroll->id)->selectRaw('
            SUM(total_orders) as orders,
            SUM(total_revenue) as revenue,
            SUM(net_salary) as salary,
            SUM(remaining_salary) as remaining,
            SUM(profit_loss) as profit,
            SUM(total_deductions) as deductions,
            COUNT(*) as drivers
        ')->first();

        return view('payroll.show', compact('payroll', 'entries', 'totals'));
    }

    public function calculate(Request $request, PayrollPeriod $period)
    {
        try {
            $month = $period->month->format('Y-m-01');
            $settings = $period->platform->settingsForMonth($month);
            
            $appRecordEmpIds = \App\Models\AppDailyRecord::where('platform_id', $period->platform_id)
                ->whereBetween('record_date', [
                    date('Y-m-01', strtotime($month)),
                    date('Y-m-t', strtotime($month)),
                ])
                ->whereNotNull('employee_id')
                ->distinct()
                ->pluck('employee_id')
                ->toArray();

            $missingEmployees = \App\Models\Employee::whereIn('id', $appRecordEmpIds)
                ->where(function($q) {
                    $q->whereNull('agreed_salary')->orWhere('agreed_salary', '<=', 0);
                })
                ->get();

            if ($missingEmployees->isNotEmpty()) {
                if (!$request->has('apply_defaults')) {
                    $missingData = [];
                    foreach ($missingEmployees as $emp) {
                        $default = 2500;
                        if ($emp->salary_system === 'commission_tiered' || $emp->contract_type === 'commission') {
                            $default = $settings->per_order_rate ?? 8;
                        } elseif ($settings && $settings->basic_salary) {
                            $default = $settings->basic_salary;
                        } elseif ($settings && $settings->base_salary_value) {
                            $default = $settings->base_salary_value;
                        }
                        $missingData[] = [
                            'id' => $emp->id,
                            'name' => $emp->name_ar ?: $emp->name_en,
                            'iqama' => $emp->iqama_number,
                            'proposed_salary' => $default
                        ];
                    }
                    return back()->with('missing_salaries', $missingData);
                } else {
                    $defaultsToApply = json_decode($request->input('defaults_to_apply', '[]'), true);
                    if (is_array($defaultsToApply)) {
                        foreach ($defaultsToApply as $empId => $defaultVal) {
                            \App\Models\Employee::where('id', $empId)->update(['agreed_salary' => $defaultVal]);
                        }
                    }
                }
            }

            $count = $this->calculator->calculateFullPayroll($period);
            return redirect()->route('payroll.show', $period)
                ->with('success', "تم احتساب رواتب {$count} موظف بنجاح");
        } catch (\Exception $e) {
            return back()->with('error', 'فشل الاحتساب: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, PayrollPeriod $period)
    {
        $this->authorize('approve payroll');
        $period->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        return back()->with('success', 'تمت الموافقة على المسير');
    }

    public function export(PayrollPeriod $period)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PayrollExport($period), 
            "payroll_{$period->platform->name_en}_{$period->month->format('Y-m')}.xlsx"
        );
    }

    public function slip(PayrollPeriod $period, PayrollEntry $entry)
    {
        $entries = collect([$entry]);
        $pdf = Pdf::loadView('payroll.slip-english-batch', compact('period', 'entries'))
            ->setPaper('a4', 'portrait');
            
        return $pdf->stream("slip_{$entry->employee->iqama_number}_{$period->month->format('Y-m')}.pdf");
    }

    public function batchPrintSlips(Request $request, PayrollPeriod $period)
    {
        $query = PayrollEntry::where('payroll_period_id', $period->id)->with('employee.branch');

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('employee_ids')) {
            $ids = is_array($request->employee_ids) ? $request->employee_ids : explode(',', $request->employee_ids);
            $query->whereIn('employee_id', $ids);
        }

        $entries = $query->orderBy('employee_id')->get();

        if ($entries->isEmpty()) {
            return back()->with('error', 'لا يوجد موظفين للطباعة بناءً على الفلاتر المحددة.');
        }

        $pdf = Pdf::loadView('payroll.slip-english-batch', compact('period', 'entries'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("slips_{$period->platform->name_en}_{$period->month->format('Y-m')}.pdf");
    }

    public function destroy(PayrollPeriod $payroll)
    {
        if (in_array($payroll->status, ['draft', 'calculated'])) {
            $payroll->entries()->delete();
            $payroll->delete();
            return redirect()->route('payroll.index')->with('success', 'تم حذف المسير نهائياً.');
        }
        
        return back()->with('error', 'لا يمكن حذف مسير معتمد أو مصروف.');
    }
}
