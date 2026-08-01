<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\Platform;
use App\Services\IqamaResolutionService;
use App\Support\PlatformColumnMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    protected IqamaResolutionService $resolver;

    public function __construct(IqamaResolutionService $resolver)
    {
        $this->resolver = $resolver;
    }

    public function index()
    {
        // المنصات مع report_format لكل منها
        $platforms    = Platform::where('is_active', true)->get();

        $sheetGroups  = PlatformColumnMap::sheetGroups();

        // بناء column defs لكل منصة (لـ JavaScript)
        $platformDefs = [];
        foreach ($platforms as $p) {
            $def = PlatformColumnMap::get($p->report_format ?? 'ninja');
            $platformDefs[$p->id] = [
                'report_format' => $p->report_format ?? 'ninja',
                'label'         => $def['label'] ?? $p->name,
                'columns'       => $def['columns'] ?? [],
                'required'      => $def['required'] ?? [],
                'notes'         => $def['notes'] ?? [],
                'id_text'       => $def['id_as_text'] ?? false,
                'used_in_calc'  => $def['used_in_calc'] ?? [],
                'info_only'     => $def['info_only'] ?? [],
            ];
        }

        // column defs لأنواع الأوراق الأخرى (الخصومات وغيرها)
        $otherDefs = [];
        foreach (PlatformColumnMap::sheetGroups() as $group => $types) {
            foreach (array_keys($types) as $type) {
                $def = PlatformColumnMap::getSheetDef($type);
                if (!empty($def)) {
                    $otherDefs[$type] = [
                        'label'    => $def['label'],
                        'columns'  => $def['columns'],
                        'required' => $def['required'] ?? [],
                        'notes'    => $def['notes'] ?? [],
                        'id_text'  => $def['id_as_text'] ?? false,
                    ];
                }
            }
        }

        return view('import.index', compact(
            'platforms', 'sheetGroups', 'platformDefs', 'otherDefs'
        ));
    }

    public function processing()
    {
        $recentBatches = ImportBatch::with('platform', 'importedBy')
            ->latest()->limit(50)->get();

        return view('import.processing', compact('recentBatches'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'platform_id' => 'nullable|exists:platforms,id',
            'month'       => 'required|date_format:Y-m',
            'sheet_type'  => 'required|string',
            'file'        => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        if (in_array($request->sheet_type, ['app_report', 'id_changes']) && empty($request->platform_id)) {
            return back()->withErrors(['platform_id' => 'يجب تحديد المنصة لهذا النوع من الملفات'])->withInput();
        }

        $platform  = $request->platform_id ? Platform::findOrFail($request->platform_id) : null;
        $month     = $request->month . '-01';
        $sheetType = $request->sheet_type;
        $file      = $request->file('file');
        $fileName  = $file->getClientOriginalName();

        $path  = $file->store('imports', 'local');

        $batch = ImportBatch::create([
            'platform_id' => $platform ? $platform->id : null,
            'month'       => $month,
            'sheet_type'  => $sheetType,
            'file_name'   => $fileName,
            'status'      => 'processing',
            'imported_by' => auth()->id(),
        ]);

        try {
            \Maatwebsite\Excel\Imports\HeadingRowFormatter::default('none');
            $importer = $this->getImporter($sheetType, $batch);

            if ($importer) {
                Excel::import($importer->setBatch($batch), Storage::disk('local')->path($path));

                $errors = $importer->getErrors();
                if (method_exists($importer, 'getWarnings')) {
                    $errors = array_merge($errors, $importer->getWarnings());
                }

                $updateData = [
                    'status'        => 'done',
                    'rows_imported' => $importer->getRowCount(),
                    'rows_failed'   => $importer->getFailedCount(),
                    'errors'        => $errors,
                ];

                // تخزين الأعمدة غير المعروفة (إن وجدت)
                if (method_exists($importer, 'getUnknownColumns')) {
                    $unknownCols = $importer->getUnknownColumns();
                    if (!empty($unknownCols)) {
                        $updateData['unknown_columns'] = $unknownCols;
                    }
                }

                $batch->update($updateData);
            } else {
                $batch->update(['status' => 'done']);
            }
        } catch (\Exception $e) {
            $batch->update([
                'status' => 'failed',
                'errors' => [['message' => $e->getMessage()]],
            ]);
            return back()->with('error', 'فشل الاستيراد: ' . $e->getMessage());
        }

        // تقرير التطبيق → معالجة الإقامات
        if ($sheetType === 'app_report' && $batch->status === 'done') {
            $msg = "تم استيراد {$batch->rows_imported} سجل بنجاح.";

            // تحذير الأعمدة غير المعروفة
            if (!empty($batch->unknown_columns)) {
                $cols = implode('، ', $batch->unknown_columns);
                $msg .= " ⚠️ أعمدة لم تُعرَّف في الحسبة: [{$cols}]";
            }

            return redirect()->route('import.index')
                ->with('success', $msg)
                ->with('process_batch_id', $batch->id);
        }

        $label = PlatformColumnMap::label($sheetType);
        
        if (property_exists($importer, 'insertedCount')) {
            $inserted = $importer->insertedCount;
            $updated = $importer->updatedCount;
            $unchanged = property_exists($importer, 'unchangedCount') ? $importer->unchangedCount : 0;
            $failedMsg = $batch->rows_failed > 0 ? " وفشل ({$batch->rows_failed}) سجل بسبب أخطاء." : "";
            
            if ($sheetType === 'employees') {
                $msg = "تم رفع {$label} بنجاح. تم إضافة ({$inserted}) موظف جديد، وتحديث بيانات ({$updated}) موظف فعلياً، بينما كان هناك ({$unchanged}) موظف بياناتهم مطابقة تماماً للقديمة ولم يتم تغييرها." . $failedMsg;
            } else {
                $msg = "تم استيراد '{$label}' بنجاح: إضافة ({$inserted}) سجل جديد، وتحديث ({$updated}) سجل فعلياً، بينما تم تجاهل ({$unchanged}) سجل لأن قيمتها مطابقة للسابق ولم تتغير." . $failedMsg;
            }
        } else {
            $failedMsg = $batch->rows_failed > 0 ? " مع وجود ({$batch->rows_failed}) سجل فاشل." : "";
            $msg = "تم استيراد '{$label}' بنجاح ({$batch->rows_imported} سجل)" . $failedMsg;
        }

        return redirect()->route('import.index')->with('success', $msg);
    }

    public function records(ImportBatch $batch)
    {
        if ($batch->sheet_type === 'id_changes') {
            $records = $batch->employeePlatformIds()->with('employee')->paginate(50);
        } elseif ($batch->sheet_type === 'employees') {
            $records = \App\Models\Employee::where('import_batch_id', $batch->id)->paginate(50);
        } else {
            $records = $batch->appDailyRecords()->with('employee')->paginate(50);
        }
        
        return view('import.records', compact('batch', 'records'));
    }

    public function process(Request $request, ImportBatch $batch)
    {
        try {
            $result = $this->resolver->processAll($batch);
            return redirect()->route('import.reconciliation', $batch)
                ->with('success', "تمت المعالجة: {$result['single_users']} ثابت و {$result['revenues_direct']} مباشر، يرجى استكمال الفروقات إن وجدت.")
                ->with('resolve_result', $result);
        } catch (\Exception $e) {
            return back()->with('error', 'فشلت المعالجة: ' . $e->getMessage());
        }
    }

    public function resolveAction(Request $request, ImportBatch $batch)
    {
        $action = $request->input('action');
        
        try {
            if ($action === 'manual_upload') {
                if (!$request->hasFile('resolution_file')) {
                    return back()->with('error', 'يرجى اختيار ملف الإكسل للرفع.');
                }
                
                $import = new \App\Imports\ManualResolutionImport($batch->id);
                \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('resolution_file'));
                
                $msg = "تم قراءة الملف وربط {$import->resolvedCount} سجل بنجاح.";
                if ($import->failedCount > 0) {
                    $msg .= " (فشل ربط {$import->failedCount} سجل لأن الإقامات غير موجودة في الموظفين).";
                    
                    $cacheKey = 'failed_manual_' . $batch->id;
                    \Illuminate\Support\Facades\Cache::put($cacheKey, $import->failedRows, now()->addMinutes(30));
                    
                    return back()->with('success', $msg)->with('failed_excel_link', route('import.export_failed_manual', $batch->id));
                }
                
                return back()->with('success', $msg);
            } elseif ($action === 'auto_fallback') {
                $result = $this->resolver->resolveSettlements($batch->id, $batch->platform_id);
                return back()->with('success', "تم ربط {$result['via_shift']} عبر الشفت و {$result['via_wallet']} بتاريخ المخالفة.");
            } elseif ($action === 'undo_adjustments') {
                $count = $this->resolver->undoAdjustments($batch->id);
                return back()->with('success', "تم إلغاء توزيع {$count} تسوية وإعادتها للفروقات.");
            }
            
            return back()->with('error', 'إجراء غير معروف.');
        } catch (\Exception $e) {
            return back()->with('error', 'فشل الربط: ' . $e->getMessage());
        }
    }

    public function reconciliation(ImportBatch $batch)
    {
        $batch->load('platform');
        $unresolvedRecords = $this->resolver->getUnresolvedRecords($batch->id);
        $duplicateRecords  = $this->resolver->getDuplicateRecords($batch->id);

        $total    = $batch->appDailyRecords()->count();
        $resolved = $batch->appDailyRecords()->whereNotNull('resolved_iqama')->where('resolve_method', '!=', 'unresolved')->count();
        $accuracy = $total > 0 ? round(($resolved / $total) * 100, 1) : 0;

        $resolveStats = [
            'total'            => $total,
            'single_users'     => $batch->appDailyRecords()->where('resolve_method', 'single_user_id')->count(),
            'direct_revenue'   => $batch->appDailyRecords()->where('resolve_method', 'direct')->where('suppliers_costs', '!=', 0)->count(),
            'direct_adjust'    => $batch->appDailyRecords()->where('resolve_method', 'direct')->where('suppliers_costs', 0)->count(),
            'manual_excel'     => $batch->appDailyRecords()->where('resolve_method', 'manual_excel')->count(),
            'wallet'           => $batch->appDailyRecords()->where('resolve_method', 'wallet_date')->count(),
            'wallet_fallback'  => $batch->appDailyRecords()->where('resolve_method', 'wallet_fallback')->count(),
            'shift'            => $batch->appDailyRecords()->where('resolve_method', 'shift_match')->count(),
            'fallback'         => $batch->appDailyRecords()->where('resolve_method', 'date_fallback')->count(),
            'unresolved'       => $unresolvedRecords->count(),
            'accuracy'         => $accuracy,
        ];

        return view('import.reconciliation', compact('batch', 'unresolvedRecords', 'duplicateRecords', 'resolveStats'));
    }

    public function exportUnresolved(ImportBatch $batch)
    {
        $records = $this->resolver->getUnresolvedRecords($batch->id);

        $platformName = $batch->platform->name ?? 'unknown';
        $fileName = 'unresolved_records_' . date('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UnresolvedRecordsExport($records, $platformName),
            $fileName
        );
    }

    public function exportRecordsByType(ImportBatch $batch, $type)
    {
        $query = $batch->appDailyRecords();
        
        switch ($type) {
            case 'single_users':
                $query->where('resolve_method', 'single_user_id');
                break;
            case 'direct_revenue':
                $query->where('resolve_method', 'direct')->where('suppliers_costs', '!=', 0);
                break;
            case 'direct_adjust':
                $query->where('resolve_method', 'direct')->where('suppliers_costs', 0);
                break;
            case 'manual_excel':
                $query->where('resolve_method', 'manual_excel');
                break;
            case 'shift':
                $query->where('resolve_method', 'shift_match');
                break;
            case 'wallet':
                $query->where('resolve_method', 'wallet_date');
                break;
            case 'wallet_fallback':
                $query->where('resolve_method', 'wallet_fallback');
                break;
            case 'fallback':
                $query->where('resolve_method', 'date_fallback');
                break;
            case 'unresolved':
                $query->where(function ($q) {
                    $q->whereNull('resolved_iqama')
                      ->orWhere('resolved_iqama', '')
                      ->orWhere('resolve_method', 'unresolved');
                });
                break;
            default:
                abort(404);
        }

        $records = $query->orderBy('record_date')->get();
        $platformName = $batch->platform->name ?? 'unknown';
        $fileName = $type . '_records_' . date('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\UnresolvedRecordsExport($records, $platformName),
            $fileName
        );
    }

    public function exportFailedManual(ImportBatch $batch)
    {
        $cacheKey = 'failed_manual_' . $batch->id;
        $failedRows = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        if (!$failedRows) {
            return back()->with('error', 'انتهت صلاحية الملف أو لا توجد سجلات فاشلة.');
        }

        $export = new class($failedRows) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $rows;
            public function __construct($rows) { $this->rows = collect($rows); }
            public function collection() { return $this->rows; }
            public function headings(): array { 
                $firstRow = $this->rows->first();
                return $firstRow ? array_keys($firstRow) : [];
            }
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, 'failed_iqamas_not_found_' . $batch->id . '.xlsx');
    }

    public function exportErrors(ImportBatch $batch)
    {
        $errors = array_merge($batch->errors ?? [], $batch->warnings ?? []);
        $rows = [];
        $cellColors = [];
        $headings = [];
        $rowIndex = 2; // 1 is for headings
        
        foreach ($errors as $error) {
            if (isset($error['row'])) {
                $row = $error['row'];
                
                // Format dates for main row
                foreach ($row as $k => $v) {
                    if (is_numeric($v) && (str_contains(strtolower($k), 'date') || str_contains(strtolower($k), 'تاريخ'))) {
                        $row[$k] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d');
                    }
                }
                
                $msg = $error['message'] ?? '';
                $row['ملاحظة الخطأ'] = $msg;
                
                $rows[] = $row;
                
                if (empty($headings)) {
                    $headings = array_keys($row);
                }
                
                $colCaptainLetter = null;
                $colIqamaLetter = null;
                
                foreach ($headings as $i => $h) {
                    $cleanH = strtolower(trim($h));
                    if ($cleanH === 'captain_id' || $cleanH === 'id') {
                        $colCaptainLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    }
                    if (str_contains($cleanH, 'iqama')) {
                        $colIqamaLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    }
                }
                
                // Yellow background for captain_id column by default for the uploaded row
                if ($colCaptainLetter) {
                    $cellColors[$colCaptainLetter . $rowIndex] = 'FFFFFF00';
                }
                
                // Determine error type colors
                $isCheck1 = str_contains($msg, 'موظف آخر') || str_contains($msg, 'شخصين على نفس') || str_contains($msg, 'إيقاف (تأثير مالي)') || str_contains($msg, 'المعرف مسجل للموظف');
                $isCheck2 = str_contains($msg, 'نفس الفترة') || str_contains($msg, 'معرفين') || str_contains($msg, 'شغل على المعرف') || str_contains($msg, 'لديه آي دي آخر');
                $isMissingIqama = str_contains($msg, 'غير مسجل في صفحة الموظفين');

                if ($isCheck1 && $colCaptainLetter) {
                    $cellColors[$colCaptainLetter . $rowIndex] = 'FFFF0000'; // Red
                } elseif (($isCheck2 || $isMissingIqama) && $colIqamaLetter) {
                    $cellColors[$colIqamaLetter . $rowIndex] = 'FFFF0000'; // Red
                }
                
                $rowIndex++;
            }
        }

        if (empty($rows)) {
            return back()->with('error', 'لا يوجد بيانات سطور مرفقة لتنزيلها. قد تكون الأخطاء من إصدار قديم.');
        }

        $fileName = 'errors_' . $batch->sheet_type . '_' . date('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ErrorsExport(collect($rows), $cellColors),
            $fileName
        );
    }

    public function status(ImportBatch $batch)
    {
        return response()->json([
            'status'        => $batch->status,
            'rows_imported' => $batch->rows_imported,
            'rows_failed'   => $batch->rows_failed,
        ]);
    }

    public function destroy(ImportBatch $batch)
    {
        // حذف الملف من الاستورج إن وجد
        if ($batch->file_name) {
            // يمكننا البحث عنه في مجلد imports وحذفه إذا لزم الأمر
        }

        // بناء على طلب العميل، لا يتم حذف الموظفين، فقط تصفير معرف الاستيراد
        if ($batch->sheet_type === 'employees') {
            \App\Models\Employee::where('import_batch_id', $batch->id)->update(['import_batch_id' => null]);
        } elseif ($batch->sheet_type === 'app_report') {
            $batch->appDailyRecords()->delete();
        } elseif ($batch->sheet_type === 'id_changes') {
            $batch->employeePlatformIds()->delete();
        }

        $batch->delete();

        return back()->with('success', 'تم حذف السجل بنجاح.');
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    protected function getImporter(string $sheetType, ImportBatch $batch)
    {
        if ($sheetType === 'app_report') {
            return new \App\Imports\AppReportImport();
        }

        return match($sheetType) {
            'id_changes'         => new \App\Imports\IdChangesImport(),
            'unified_deductions' => new \App\Imports\UnifiedDeductionsImport(),
            'maintenance'      => new \App\Imports\MaintenanceImport(),
            'penalties'        => new \App\Imports\PenaltiesImport(),
            'pre_salary'       => new \App\Imports\PreSalaryImport(),
            'fuel', 'housing', 'packages' => (function () use ($sheetType) {
                require_once app_path('Imports/ExpenseImports.php');
                return match($sheetType) {
                    'fuel'             => new \App\Imports\FuelImport(),
                    'housing'          => new \App\Imports\HousingImport(),
                    'packages'         => new \App\Imports\PackagesImport(),
                };
            })(),
            default => null,
        };
    }
}
