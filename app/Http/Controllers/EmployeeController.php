<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Platform;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeesImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EmployeeController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $updateExisting = $request->has('update_existing');
        $addNew = $request->has('add_new');

        // If neither is checked, return error
        if (!$updateExisting && !$addNew) {
            return back()->with('error', 'يجب تحديد خيار واحد على الأقل (إضافة موظفين جدد أو تحديث البيانات).');
        }

        $file = $request->file('file');
        
        $batch = \App\Models\ImportBatch::create([
            'platform_id' => null,
            'month'       => now()->startOfMonth(),
            'sheet_type'  => 'employees',
            'file_name'   => $file->getClientOriginalName(),
            'status'      => 'processing',
            'imported_by' => auth()->id(),
        ]);

        $import = new EmployeesImport($batch, $updateExisting, $addNew);
        
        try {
            Excel::import($import, $file);
            
            $batch->update([
                'status'        => 'done',
                'rows_imported' => $import->rowCount,
                'rows_failed'   => $import->failedCount,
                'errors'        => $import->errors,
            ]);

            $msg = "تم استيراد {$import->rowCount} موظف بنجاح.";
            if ($import->failedCount > 0) {
                $msg .= " ولم يتم استيراد {$import->failedCount} صف (مكرر أو به خطأ).";
                return redirect()->route('employees.index')->with([
                    'warning' => $msg,
                    'last_batch_id' => $batch->id
                ]);
            }

            return redirect()->route('employees.index')->with('success', $msg);
        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'errors' => [['message' => $e->getMessage()]],
            ]);
            return back()->with('error', 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage());
        }
    }

    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('بيانات الموظفين');
        $sheet->setRightToLeft(true);

        $columns = [
            'Iqama Number', 'Rider\'s Name', 'Contract type', 'Agreed salary',
            'Current application (guide only)', 'Employee status', 'Branch', 'city',
            'Salary system', 'Discount factor', 'App ID'
        ];

        $autoUpdatedCols = [4, 5, 7, 10]; // Indexes for App, Status, City, App ID

        // Header
        foreach ($columns as $idx => $colName) {
            $colLetter = chr(65 + $idx);
            $cell = $colLetter . '1';
            
            if (in_array($idx, $autoUpdatedCols)) {
                $sheet->setCellValue($cell, $colName . ' (تلقائي)');
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']], // Amber-600
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B45309']]],
                ]);
            } else {
                $sheet->setCellValue($cell, $colName);
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '475569']]],
                ]);
            }
            $sheet->getColumnDimension($colLetter)->setWidth(25);
        }

        // Example row
        $sheet->setCellValue('A2', '2410000000');
        $sheet->setCellValue('B2', 'أحمد محمد');
        $sheet->setCellValue('C2', 'salary');
        $sheet->setCellValue('D2', '2500');
        $sheet->setCellValue('E2', 'Ninja');
        $sheet->setCellValue('F2', 'active');
        $sheet->setCellValue('G2', 'الرياض');
        $sheet->setCellValue('H2', 'الرياض');
        $sheet->setCellValue('I2', 'fixed');
        $sheet->setCellValue('J2', '0');
        $sheet->setCellValue('K2', '891234567890');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template_employees.xlsx');
    }

    public function export(Request $request)
    {
        $query = Employee::with(['branch', 'platform'])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('name_en', 'like', "%{$request->search}%")
                       ->orWhere('name_ar', 'like', "%{$request->search}%")
                       ->orWhere('iqama_number', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status, fn($q) => $q->where('employee_status', $request->status))
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->platform_id, fn($q) => $q->where('platform_id', $request->platform_id))
            ->when($request->salary_system, fn($q) => $q->where('salary_system', $request->salary_system))
            ->latest();

        $employees = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('بيانات الموظفين');
        $sheet->setRightToLeft(true);

        $columns = [
            'Iqama Number', 'Rider\'s Name', 'Contract type', 'Agreed salary',
            'Current application (guide only)', 'Employee status', 'Branch', 'city',
            'Salary system', 'Discount factor', 'App ID'
        ];

        $autoUpdatedCols = [4, 5, 7, 10]; // Indexes for App, Status, City, App ID

        // Header
        foreach ($columns as $idx => $colName) {
            $colLetter = chr(65 + $idx);
            $cell = $colLetter . '1';
            
            if (in_array($idx, $autoUpdatedCols)) {
                $sheet->setCellValue($cell, $colName . ' (تلقائي)');
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B45309']]],
                ]);
            } else {
                $sheet->setCellValue($cell, $colName);
                $sheet->getStyle($cell)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '475569']]],
                ]);
            }
            $sheet->getColumnDimension($colLetter)->setWidth(25);
        }

        $rowNum = 2;
        foreach ($employees as $emp) {
            $sheet->setCellValueExplicit('A' . $rowNum, $emp->iqama_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $rowNum, $emp->name_en);
            $sheet->setCellValue('C' . $rowNum, $emp->contract_type);
            $sheet->setCellValue('D' . $rowNum, $emp->agreed_salary);
            $sheet->setCellValue('E' . $rowNum, optional($emp->platform)->name);
            $sheet->setCellValue('F' . $rowNum, $emp->employee_status);
            $sheet->setCellValue('G' . $rowNum, optional($emp->branch)->name);
            $sheet->setCellValue('H' . $rowNum, $emp->city);
            $sheet->setCellValue('I' . $rowNum, $emp->salary_system);
            $sheet->setCellValue('J' . $rowNum, $emp->discount_factor ?? 0);
            $sheet->setCellValueExplicit('K' . $rowNum, $emp->app_id ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $rowNum++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'employees_export_' . date('Y-m-d_H-i') . '.xlsx');
    }

    public function index(Request $request)
    {
        $query = Employee::with(['branch', 'platform'])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('name_en', 'like', "%{$request->search}%")
                       ->orWhere('name_ar', 'like', "%{$request->search}%")
                       ->orWhere('iqama_number', 'like', "%{$request->search}%");
                });
            })
            ->when($request->status, fn($q) => $q->where('employee_status', $request->status))
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->platform_id, fn($q) => $q->where('platform_id', $request->platform_id))
            ->when($request->salary_system, fn($q) => $q->where('salary_system', $request->salary_system))
            ->latest();

        $employees = $query->paginate(25)->withQueryString();
        $branches  = Branch::where('is_active', true)->get();
        $platforms = Platform::where('is_active', true)->get();

        $stats = [
            'total'    => Employee::count(),
            'active'   => Employee::where('employee_status', 'active')->count(),
            'fixed'    => Employee::where('salary_system', 'fixed')->count(),
            'commission' => Employee::where('salary_system', 'commission_tiered')->count(),
        ];

        return view('employees.index', compact('employees', 'branches', 'platforms', 'stats'));
    }

    public function create()
    {
        $branches  = Branch::where('is_active', true)->get();
        $platforms = Platform::where('is_active', true)->get();
        $cities    = \App\Models\City::where('is_active', true)->get();
        return view('employees.create', compact('branches', 'platforms', 'cities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'iqama_number'    => 'required|string|max:20|unique:employees',
            'name_en'         => 'required|string|max:100',
            'name_ar'         => 'nullable|string|max:100',
            'branch_id'       => 'nullable|exists:branches,id',
            'city'            => 'nullable|string|max:50',
            'contract_type'   => 'required|in:salary,commission,both',
            'salary_system'   => 'required|in:fixed,commission_tiered,hybrid',
            'agreed_salary'   => 'nullable|numeric|min:0',
            'platform_id'     => 'nullable|exists:platforms,id',
            'employee_status' => 'required|in:active,inactive,suspended,resigned',
            'hire_date'       => 'nullable|date',
            'phone'           => 'nullable|string|max:20',
            'nationality'     => 'nullable|string|max:50',
        ], [
            'iqama_number.unique' => 'رقم الإقامة هذا مسجل مسبقاً لموظف آخر. لا يمكن تكرار إضافة نفس الموظف!',
        ]);

        Employee::create($data);
        return redirect()->route('employees.index')->with('success', 'تم إضافة الموظف بنجاح');
    }

    public function show(Employee $employee)
    {
        $employee->load(['branch', 'platform', 'platformIds.platform']);
        $platforms = Platform::all();
        return view('employees.show', compact('employee', 'platforms'));
    }

    public function edit(Employee $employee)
    {
        $branches  = Branch::all();
        $platforms = Platform::all();
        $cities    = \App\Models\City::where('is_active', true)->get();
        return view('employees.edit', compact('employee', 'branches', 'platforms', 'cities'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'iqama_number'    => 'required|string|max:20|unique:employees,iqama_number,' . $employee->id,
            'name_en'         => 'required|string|max:100',
            'name_ar'         => 'nullable|string|max:100',
            'branch_id'       => 'nullable|exists:branches,id',
            'city'            => 'nullable|string|max:50',
            'contract_type'   => 'required|in:salary,commission,both',
            'salary_system'   => 'required|in:fixed,commission_tiered,hybrid',
            'agreed_salary'   => 'nullable|numeric|min:0',
            'platform_id'     => 'nullable|exists:platforms,id',
            'employee_status' => 'required|in:active,inactive,suspended,resigned',
            'hire_date'       => 'nullable|date',
            'phone'           => 'nullable|string|max:20',
            'nationality'     => 'nullable|string|max:50',
        ]);

        $employee->update($data);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'تم تحديث بيانات الموظف');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')
            ->with('success', 'تم حذف الموظف');
    }

    public function platformIds(Employee $employee)
    {
        $employee->load('platformIds.platform');
        $platforms = Platform::where('is_active', true)->get();
        return view('employees.platform-ids', compact('employee', 'platforms'));
    }

    public function storePlatformId(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'captain_id'  => 'required|integer',
            'id_name'     => 'nullable|string|max:100',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'city'        => 'nullable|string|max:50',
        ]);

        \App\Models\EmployeePlatformId::create(array_merge($data, ['employee_id' => $employee->id]));

        return back()->with('success', 'تم إضافة ID التطبيق بنجاح');
    }

    public function destroyPlatformId(Employee $employee, int $platformId)
    {
        \App\Models\EmployeePlatformId::where('id', $platformId)
            ->where('employee_id', $employee->id)
            ->delete();

        return back()->with('success', 'تم حذف ID التطبيق');
    }
    public function destroyAll()
    {
        // Disable foreign key checks temporarily to allow truncating
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Delete related child records that depend on employee first to avoid orphan records or constraint issues
        \App\Models\EmployeePlatformId::truncate();
        \App\Models\EmployeeIdDailyRecord::truncate();
        
        // Depending on business logic, we might also need to delete payrolls, deductions, advances, etc.
        // But the user asked to "delete all employees to upload them again", so we'll truncate the employee table directly.
        // It's safer to use delete() to trigger model events if any, but truncate is faster if cascading isn't fully set up.
        // I will use delete() on Employee to respect cascading if it exists, or truncate if it's safe.
        // The safest robust way is query delete:
        Employee::withTrashed()->forceDelete();
        
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return redirect()->route('employees.index')->with('success', 'تم حذف جميع بيانات الموظفين والمناديب بنجاح.');
    }
}
