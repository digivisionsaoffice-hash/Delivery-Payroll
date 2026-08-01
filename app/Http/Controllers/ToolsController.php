<?php

namespace App\Http\Controllers;

use App\Models\EmployeePlatformId;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ToolsController extends Controller
{
    public function cleanup()
    {
        // Find employees that have multiple distinct captain_ids
        $duplicates = EmployeePlatformId::select('employee_id', DB::raw('COUNT(DISTINCT captain_id) as count'))
            ->groupBy('employee_id')
            ->having('count', '>', 1)
            ->with('employee')
            ->get();

        $employeeIds = $duplicates->pluck('employee_id');

        $platformIds = EmployeePlatformId::whereIn('employee_id', $employeeIds)
            ->with('platform', 'employee')
            ->orderBy('employee_id')
            ->orderBy('start_date', 'desc')
            ->get();

        $grouped = $platformIds->groupBy('employee_id');

        return view('tools.cleanup', compact('grouped'));
    }

    public function removeCaptainId($id)
    {
        $platformId = EmployeePlatformId::findOrFail($id);
        $employeeId = $platformId->employee_id;
        $captainId = $platformId->captain_id;

        // Also clean up AppDailyRecord to unlink them so they become Unresolved
        \App\Models\AppDailyRecord::where('employee_id', $employeeId)
            ->where('captain_id', $captainId)
            ->update([
                'employee_id' => null,
                'resolved_iqama' => null,
                'resolve_method' => 'unresolved'
            ]);
            
        // Delete the mapping
        $platformId->delete();

        return back()->with('success', 'تم فك الارتباط بنجاح وإرجاع الطلبات المرتبطة لحالة "غير معالجة"');
    }
}
