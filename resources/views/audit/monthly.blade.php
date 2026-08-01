@extends('layouts.app')
@section('title', 'التقرير الرقابي الشامل')
@section('page-title', 'التقرير الرقابي ومطابقة المسير')

@section('content')

{{-- الفلاتر --}}
<div class="card mb-4 fade-in">
    <div class="card-body">
        <form method="GET" action="{{ route('audit.monthly') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">الشهر</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">التطبيق للتدقيق العكسي</label>
                <select name="platform_id" class="form-select">
                    <option value="">-- اختر التطبيق للمطابقة --</option>
                    @foreach($platforms as $p)
                        <option value="{{ $p->id }}" {{ $platformId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn-walim w-100">
                    <i class="bi bi-search"></i> فحص ومطابقة
                </button>
            </div>
        </form>
    </div>
</div>

{{-- الإجماليات المرفوعة --}}
<h5 class="mb-3">إجماليات الخصومات المرفوعة لشهر {{ $month }}</h5>
<div class="row g-2 mb-4">
    <div class="col-md-3 col-6 fade-in fade-in-1">
        <div class="kpi-card kpi-blue" style="padding:1rem;">
            <div class="kpi-value" style="font-size:1.4rem">{{ number_format($totals['advances'], 2) }} ر.س</div>
            <div class="kpi-label">إجمالي السلف</div>
        </div>
    </div>
    <div class="col-md-3 col-6 fade-in fade-in-2">
        <div class="kpi-card kpi-yellow" style="padding:1rem;">
            <div class="kpi-value" style="font-size:1.4rem">{{ number_format($totals['penalties'], 2) }} ر.س</div>
            <div class="kpi-label">إجمالي المخالفات</div>
        </div>
    </div>
    <div class="col-md-3 col-6 fade-in fade-in-3">
        <div class="kpi-card kpi-purple" style="padding:1rem;">
            <div class="kpi-value" style="font-size:1.4rem">{{ number_format($totals['maintenance'], 2) }} ر.س</div>
            <div class="kpi-label">إجمالي الصيانة (قطع الغيار)</div>
        </div>
    </div>
    <div class="col-md-3 col-6 fade-in fade-in-4">
        <div class="kpi-card kpi-green" style="padding:1rem;">
            <div class="kpi-value" style="font-size:1.4rem">{{ number_format($totals['pre_salary'], 2) }} ر.س</div>
            <div class="kpi-label">الرواتب المقدمة</div>
        </div>
    </div>
</div>

{{-- أخطاء ملفات الخصومات --}}
@if($importErrors->isNotEmpty())
<div class="card mb-4 border-danger fade-in">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-exclamation-triangle"></i> أخطاء استيراد ملفات الخصومات (هذا الشهر)
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>نوع الملف</th>
                    <th>السجلات المرفوضة (أرقام إقامات مفقودة)</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @foreach($importErrors as $error)
                <tr>
                    <td>{{ __('walim.' . $error['type']) ?? $error['type'] }}</td>
                    <td class="text-danger fw-bold">{{ $error['failed_count'] }} سجل مرفوض</td>
                    <td>
                        <a href="{{ route('import.export_errors', $error['batch_id']) }}" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-download"></i> تنزيل الأخطاء
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- التدقيق العكسي --}}
@if($platformId)
    <div class="card mb-4 fade-in">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">التدقيق العكسي مع تقرير التطبيق</h6>
            <span class="badge bg-secondary">سجلات التطبيق المقروءة: {{ number_format($appRecordsCount) }}</span>
        </div>
        <div class="card-body">
            @if($missingFromApp->isEmpty())
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle-fill"></i> جميع الموظفين الذين عليهم خصومات موجودون في كشف هذا التطبيق. لا يوجد أي تسريب!
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>تنبيه:</strong> وجدنا <strong>{{ $missingFromApp->count() }}</strong> موظفاً عليهم خصومات في النظام (هذا الشهر)، ولكن <strong>لم يعملوا (لا يوجد لهم إيراد) في هذا التطبيق</strong> هذا الشهر! قد يكونوا عملوا في تطبيق آخر أو مجازين، ولن يخصم منهم في مسير هذا التطبيق.
                </div>
                
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الموظف</th>
                                <th>رقم الإقامة</th>
                                <th>إجمالي الخصومات المطلوب خصمها</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($missingFromApp as $emp)
                            <tr>
                                <td>{{ $emp->name_en }} <br><small class="text-muted">{{ $emp->name_ar }}</small></td>
                                <td>{{ $emp->iqama_number }}</td>
                                <td class="text-danger fw-bold">{{ number_format($emp->total_deductions, 2) }} ر.س</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="alert alert-info fade-in">
        <i class="bi bi-info-circle"></i> لمعرفة إذا كان هناك موظفون عليهم خصومات ولكنهم غير موجودين في كشف رواتب التطبيق، قم باختيار التطبيق من الفلتر أعلاه واضغط فحص.
    </div>
@endif

@endsection
