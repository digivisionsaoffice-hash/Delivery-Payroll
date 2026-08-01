<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\ProfitabilityController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

// الصفحة الرئيسية → تحويل لتسجيل الدخول
Route::get('/', fn() => redirect()->route('login'));

// المسارات المحمية بتسجيل الدخول
Route::middleware(['auth'])->group(function () {

    // لوحة التحكم
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // الموظفون والسائقون
    Route::post('employees/import', [EmployeeController::class, 'upload'])->name('employees.import');
    Route::get('employees/template', [EmployeeController::class, 'template'])->name('employees.template');
    Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::delete('employees/destroy-all', [EmployeeController::class, 'destroyAll'])->name('employees.destroy_all');
    Route::resource('employees', EmployeeController::class);
    Route::get('employees/{employee}/ids', [EmployeeController::class, 'platformIds'])->name('employees.platform-ids');
    Route::post('employees/{employee}/ids', [EmployeeController::class, 'storePlatformId'])->name('employees.platform-ids.store');
    Route::delete('employees/{employee}/ids/{platformId}', [EmployeeController::class, 'destroyPlatformId'])->name('employees.platform-ids.destroy');

    // المنصات
    Route::resource('platforms', PlatformController::class);
    Route::post('platforms/{platform}/settings', [PlatformController::class, 'storeSettings'])->name('platforms.settings.store');
    Route::delete('platforms/settings/{setting}', [PlatformController::class, 'destroySettings'])->name('platforms.settings.destroy');

    Route::delete('branches/destroy-all', [BranchController::class, 'destroyAll'])->name('branches.destroy_all');
    Route::resource('branches', BranchController::class)->except(['show']);

    // المدن والمناطق
    Route::delete('cities/destroy-all', [App\Http\Controllers\CityController::class, 'destroyAll'])->name('cities.destroy_all');
    Route::resource('cities', App\Http\Controllers\CityController::class)->except(['show', 'create', 'edit']);

    // استيراد البيانات
    Route::get('import', [ImportController::class, 'index'])->name('import.index');
    Route::post('import/upload', [ImportController::class, 'upload'])->name('import.upload');
    Route::get('import/processing', [ImportController::class, 'processing'])->name('import.processing');
    Route::post('import/{batch}/process', [ImportController::class, 'process'])->name('import.process');
    Route::delete('import/{batch}', [ImportController::class, 'destroy'])->name('import.destroy');
    Route::get('import/{batch}/export-errors', [ImportController::class, 'exportErrors'])->name('import.export_errors');
    Route::get('import/template/{platform}/{type}', [TemplateController::class, 'downloadAppReportTemplate'])->name('import.template.app_report');
    Route::get('import/{batch}/records', [ImportController::class, 'records'])->name('import.records');
    Route::get('import/{batch}/reconciliation', [ImportController::class, 'reconciliation'])->name('import.reconciliation');
    Route::post('import/{batch}/resolve-action', [ImportController::class, 'resolveAction'])->name('import.resolve_action');
    Route::get('import/{batch}/export-failed-manual', [ImportController::class, 'exportFailedManual'])->name('import.export_failed_manual');
    Route::get('import/{batch}/export-unresolved', [ImportController::class, 'exportUnresolved'])->name('import.export_unresolved');
    Route::get('import/{batch}/export-records/{type}', [ImportController::class, 'exportRecordsByType'])->name('import.export_records');
    Route::get('import/{batch}/status', [ImportController::class, 'status'])->name('import.status');

    // Audit and Reconciliation
    Route::get('/audit/monthly', [App\Http\Controllers\AuditController::class, 'monthly'])->name('audit.monthly');

    // قوالب Excel للتحميل
    Route::get('templates/platform/{platformId}', [TemplateController::class, 'platformReport'])->name('template.platform');
    Route::get('templates/sheet/{type}', [TemplateController::class, 'sheetType'])->name('template.sheet');

    // مسير الرواتب
    Route::resource('payroll', PayrollController::class)->except(['edit', 'update']);
    Route::post('payroll/{period}/calculate', [PayrollController::class, 'calculate'])->name('payroll.calculate');
    Route::post('payroll/{period}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
    Route::get('payroll/{period}/export', [PayrollController::class, 'export'])->name('payroll.export');
    Route::post('payroll/{period}/slips/batch', [PayrollController::class, 'batchPrintSlips'])->name('payroll.slips.batch');
    Route::get('payroll/{period}/slip/{entry}', [PayrollController::class, 'slip'])->name('payroll.slip');

    // الخصومات والسلف
    Route::get('deductions', [DeductionController::class, 'index'])->name('deductions.index');
    Route::get('deductions/advances', [DeductionController::class, 'advances'])->name('deductions.advances');
    Route::post('deductions/advances', [DeductionController::class, 'storeAdvance'])->name('deductions.advances.store');
    Route::get('deductions/violations', [DeductionController::class, 'violations'])->name('deductions.violations');

    // الربحية
    Route::get('profitability', [ProfitabilityController::class, 'index'])->name('profitability.index');
    Route::get('profitability/driver/{employee}', [ProfitabilityController::class, 'driver'])->name('profitability.driver');
    Route::get('profitability/platform/{platform}', [ProfitabilityController::class, 'platform'])->name('profitability.platform');

    // التقارير
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/payroll', [ReportController::class, 'payrollReport'])->name('reports.payroll');
    Route::get('reports/profitability', [ReportController::class, 'profitabilityReport'])->name('reports.profitability');
    Route::get('reports/drivers', [ReportController::class, 'driversReport'])->name('reports.drivers');
    Route::get('reports/anomalies', [ReportController::class, 'anomalies'])->name('reports.anomalies');
    Route::get('reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');

    // التقارير الجديدة
    Route::get('reports/performance', [ReportController::class, 'performance'])->name('reports.performance');
    Route::get('reports/deductions-summary', [ReportController::class, 'deductionsSummary'])->name('reports.deductions');
    Route::get('reports/inactive-drivers', [ReportController::class, 'inactiveDrivers'])->name('reports.inactive');

    // الإعدادات والأدوات
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('tools/cleanup', [App\Http\Controllers\ToolsController::class, 'cleanup'])->name('tools.cleanup');
    Route::post('tools/cleanup/{id}', [App\Http\Controllers\ToolsController::class, 'removeCaptainId'])->name('tools.cleanup.remove');

    // المستخدمون (للمدراء فقط)
    Route::resource('users', UserController::class)->middleware('can:manage users');

    // الملف الشخصي
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
