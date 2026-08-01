<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Employee;
use App\Models\TrafficViolation;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    public function index()
    {
        return view('deductions.index');
    }

    public function advances(Request $request)
    {
        $advances = Advance::with('employee')
            ->when($request->month, fn($q) => $q->where('payroll_month', $request->month . '-01'))
            ->latest()->paginate(30);
        $employees = Employee::where('employee_status', 'active')->get();
        return view('deductions.advances', compact('advances', 'employees'));
    }

    public function storeAdvance(Request $request)
    {
        $data = $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'payroll_month' => 'required|date_format:Y-m',
            'amount'        => 'required|numeric|min:1',
            'notes'         => 'nullable|string',
        ]);
        Advance::create(['employee_id'=>$data['employee_id'],'payroll_month'=>$data['payroll_month'].'-01','amount'=>$data['amount'],'notes'=>$data['notes']??null]);
        return back()->with('success', 'تم إضافة السلفة');
    }

    public function violations(Request $request)
    {
        $violations = TrafficViolation::with('employee')
            ->when($request->month, fn($q) => $q->where('payroll_month', $request->month . '-01'))
            ->latest()->paginate(30);
        return view('deductions.violations', compact('violations'));
    }
}
