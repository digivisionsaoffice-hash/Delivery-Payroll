<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Platform;
use App\Models\Advance;
use App\Models\CompanyPenalty;
use App\Models\SparePartsMisuse;
use App\Models\PreSalaryPayment;
use App\Models\AppDailyRecord;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function monthly(Request $request)
    {
        $month = $request->input('month', date('Y-m'));
        $platformId = $request->input('platform_id');
        
        $platforms = Platform::all();
        $payrollMonth = $month . '-01';

        // Fetch total deductions for this month
        $totals = [
            'advances' => Advance::where('payroll_month', $payrollMonth)->sum('amount'),
            'penalties' => CompanyPenalty::where('payroll_month', $payrollMonth)->sum('discount_amount'),
            'maintenance' => SparePartsMisuse::where('payroll_month', $payrollMonth)->sum('cost'),
            'pre_salary' => PreSalaryPayment::where('payroll_month', $payrollMonth)->sum('amount'),
        ];

        // Fetch deduction import errors summary for this month
        // Find the latest deduction batches
        $deductionTypes = ['advances', 'unified_deductions', 'maintenance', 'penalties', 'pre_salary'];
        $importErrors = collect();
        foreach ($deductionTypes as $type) {
            $batch = ImportBatch::where('sheet_type', $type)->where('month', $payrollMonth)->latest()->first();
            if ($batch && $batch->rows_failed > 0) {
                $importErrors->push([
                    'type' => $batch->sheet_type,
                    'failed_count' => $batch->rows_failed,
                    'batch_id' => $batch->id
                ]);
            }
        }

        $missingFromApp = collect();
        $appRecordsCount = 0;

        if ($platformId) {
            $periodStart = $payrollMonth;
            $periodEnd = date('Y-m-t', strtotime($payrollMonth));

            // Total rows imported for this app
            $appRecordsCount = AppDailyRecord::where('platform_id', $platformId)
                ->whereBetween('record_date', [$periodStart, $periodEnd])
                ->count();

            // Get all employee IDs who have deductions this month
            $employeeIdsWithDeductions = collect();
            $employeeIdsWithDeductions = $employeeIdsWithDeductions->merge(Advance::where('payroll_month', $payrollMonth)->pluck('employee_id'));
            $employeeIdsWithDeductions = $employeeIdsWithDeductions->merge(CompanyPenalty::where('payroll_month', $payrollMonth)->pluck('employee_id'));
            $employeeIdsWithDeductions = $employeeIdsWithDeductions->merge(SparePartsMisuse::where('payroll_month', $payrollMonth)->pluck('employee_id'));
            $employeeIdsWithDeductions = $employeeIdsWithDeductions->merge(PreSalaryPayment::where('payroll_month', $payrollMonth)->pluck('employee_id'));
            $employeeIdsWithDeductions = $employeeIdsWithDeductions->unique();

            // Find which of these employees DO NOT have app_daily_records for this platform
            if ($employeeIdsWithDeductions->isNotEmpty()) {
                $appEmployeeIds = AppDailyRecord::where('platform_id', $platformId)
                    ->whereBetween('record_date', [$periodStart, $periodEnd])
                    ->whereIn('employee_id', $employeeIdsWithDeductions)
                    ->pluck('employee_id')
                    ->unique()
                    ->toArray();

                $missingIds = $employeeIdsWithDeductions->diff($appEmployeeIds);

                if ($missingIds->isNotEmpty()) {
                    $missingFromApp = DB::table('employees')
                        ->whereIn('id', $missingIds)
                        ->select('id', 'name_en', 'name_ar', 'iqama_number')
                        ->get()
                        ->map(function($emp) use ($payrollMonth) {
                            $adv = Advance::where('employee_id', $emp->id)->where('payroll_month', $payrollMonth)->sum('amount');
                            $pen = CompanyPenalty::where('employee_id', $emp->id)->where('payroll_month', $payrollMonth)->sum('discount_amount');
                            $maint = SparePartsMisuse::where('employee_id', $emp->id)->where('payroll_month', $payrollMonth)->sum('cost');
                            $pre = PreSalaryPayment::where('employee_id', $emp->id)->where('payroll_month', $payrollMonth)->sum('amount');
                            
                            $emp->total_deductions = $adv + $pen + $maint + $pre;
                            return $emp;
                        });
                }
            }
        }

        return view('audit.monthly', compact(
            'month', 'platformId', 'platforms', 'totals', 
            'missingFromApp', 'appRecordsCount', 'importErrors'
        ));
    }
}
