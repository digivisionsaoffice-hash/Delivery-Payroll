@extends('layouts.app')
@section('title', 'تقرير الفروقات')
@section('page-title', 'تقرير الفروقات والمطابقة')

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 fade-in">
    <div>
        <h6 class="mb-0">{{ $batch->platform->name }} — {{ \Carbon\Carbon::parse($batch->month)->format('Y/m') }}</h6>
        <small class="text-muted">{{ $batch->file_name }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('import.index') }}" class="btn-ghost">
            <i class="bi bi-arrow-right"></i> رجوع
        </a>
        <a href="{{ route('payroll.create') }}" class="btn-walim">
            <i class="bi bi-calculator"></i> احتساب الرواتب
        </a>
    </div>
</div>

<style>
.kpi-link-card {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-link-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.kpi-link-card:hover .kpi-card {
    border-color: rgba(255, 255, 255, 0.2);
}
.download-icon {
    position: absolute;
    top: 10px;
    left: 10px;
    opacity: 0;
    transition: opacity 0.2s;
}
.kpi-link-card:hover .download-icon {
    opacity: 1;
}
</style>

<div class="row g-2 mb-4">
    <!-- إجمالي السجلات -->
    <div class="col-md-4 col-6 fade-in fade-in-1">
        <div class="kpi-card kpi-blue" style="padding:1rem; position:relative;">
            <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['total']) }}</div>
            <div class="kpi-label">إجمالي السجلات</div>
        </div>
    </div>
    
    <!-- دقة المطابقة -->
    <div class="col-md-4 col-6 fade-in fade-in-4">
        <div class="kpi-card {{ $resolveStats['accuracy'] >= 95 ? 'kpi-green' : ($resolveStats['accuracy'] >= 80 ? 'kpi-yellow' : 'kpi-red') }}" style="padding:1rem; position:relative;">
            <div class="kpi-value" style="font-size:1.4rem;color:{{ $resolveStats['accuracy'] >= 95 ? 'var(--success)' : ($resolveStats['accuracy'] >= 80 ? 'var(--warning)' : 'var(--danger)') }}">
                {{ $resolveStats['accuracy'] }}%
            </div>
            <div class="kpi-label">دقة المطابقة</div>
        </div>
    </div>

    <!-- غير محلول -->
    <div class="col-md-4 col-6 fade-in fade-in-4">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'unresolved']) }}" class="kpi-link-card">
            <div class="kpi-card {{ $resolveStats['unresolved'] > 0 ? 'kpi-red' : 'kpi-green' }}" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['unresolved']) }}</div>
                <div class="kpi-label">غير محلول ⚠️</div>
            </div>
        </a>
    </div>

    <!-- المعرفات الثابتة -->
    <div class="col-md-4 col-6 fade-in fade-in-2">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'single_users']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-green" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['single_users'] ?? 0) }}</div>
                <div class="kpi-label">المعرفات الثابتة</div>
            </div>
        </a>
    </div>
    
    <!-- مباشر إيراد -->
    <div class="col-md-4 col-6 fade-in fade-in-2">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'direct_revenue']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-green" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['direct_revenue'] ?? 0) }}</div>
                <div class="kpi-label">مباشر (إيراد) ✅</div>
            </div>
        </a>
    </div>
    
    <!-- مباشر تسويات -->
    <div class="col-md-4 col-6 fade-in fade-in-2">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'direct_adjust']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-purple" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['direct_adjust'] ?? 0) }}</div>
                <div class="kpi-label">مباشر (تسويات)</div>
            </div>
        </a>
    </div>

    <!-- محلول يدوياً (عبر الإكسل) -->
    <div class="col-md-4 col-6 fade-in fade-in-2">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'manual_excel']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-blue" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['manual_excel'] ?? 0) }}</div>
                <div class="kpi-label">محلول يدوياً ✍️</div>
            </div>
        </a>
    </div>

    <!-- عبر الشفت -->
    <div class="col-md-4 col-6 fade-in fade-in-2">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'shift']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-yellow" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['shift'] ?? 0) }}</div>
                <div class="kpi-label">عبر الشفت</div>
            </div>
        </a>
    </div>

    <!-- ربط بتاريخ الولت -->
    <div class="col-md-4 col-6 fade-in fade-in-3">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'wallet']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-yellow" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['wallet'] ?? 0) }}</div>
                <div class="kpi-label">ربط بتاريخ الولت</div>
            </div>
        </a>
    </div>
    
    <!-- تراجع من الولت -->
    <div class="col-md-4 col-6 fade-in fade-in-3">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'wallet_fallback']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-yellow" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['wallet_fallback'] ?? 0) }}</div>
                <div class="kpi-label">تراجع (تاريخ الولت)</div>
            </div>
        </a>
    </div>

    <!-- تراجع تاريخي -->
    <div class="col-md-4 col-6 fade-in fade-in-3">
        <a href="{{ route('import.export_records', ['batch' => $batch->id, 'type' => 'fallback']) }}" class="kpi-link-card">
            <div class="kpi-card kpi-yellow" style="padding:1rem; position:relative;">
                <i class="bi bi-cloud-download download-icon text-muted"></i>
                <div class="kpi-value" style="font-size:1.4rem">{{ number_format($resolveStats['fallback'] ?? 0) }}</div>
                <div class="kpi-label">تراجع تاريخي (عادي)</div>
            </div>
        </a>
    </div>
</div>

{{-- تنبيه إذا وجدت فروقات --}}
@if($unresolvedRecords->count() > 0)
<div class="alert-walim alert-danger mb-4 fade-in">
    <i class="bi bi-exclamation-triangle-fill" style="color:var(--danger);font-size:1.2rem;flex-shrink:0"></i>
    <div>
        <strong>{{ $unresolvedRecords->count() }} سجل لم يُحل</strong> — هذه السجلات لا يوجد لها رقم إقامة مطابق.
        يرجى مراجعة جدول تغيير الـ IDs والتأكد من اكتمال البيانات قبل احتساب الرواتب.
    </div>
</div>
@else
<div class="alert-walim alert-success mb-4 fade-in">
    <i class="bi bi-check-circle-fill" style="color:var(--success);font-size:1.2rem;flex-shrink:0"></i>
    <div><strong>الحمد لله، لا توجد أي فروقات!</strong> جميع السجلات تم ربطها بأرقام إقامة بنجاح.</div>
</div>
@endif

{{-- السجلات غير المحلولة --}}
@if($unresolvedRecords->count() > 0)
<div class="chart-card fade-in mb-3">
    <div class="chart-header d-flex justify-content-between align-items-center">
        <div>
            <div class="chart-title d-inline-block me-2" style="color:var(--danger)">⚠️ سجلات بدون إقامة</div>
            <span class="status-badge badge-loss">{{ $unresolvedRecords->count() }}</span>
        </div>
        <a href="{{ route('import.export_unresolved', $batch) }}" class="btn-ghost" style="color:var(--text-muted)">
            <i class="bi bi-file-earmark-excel"></i> تصدير للتشغيل
        </a>
    </div>
    
    {{-- خيارات التوزيع التفاعلي --}}
    <div class="d-flex gap-2 flex-wrap mb-3 p-3" style="background:rgba(239,68,68,0.05);border-radius:12px;border:1px dashed rgba(239,68,68,0.2)">
        <div style="width:100%;font-size:0.85rem;color:var(--text-muted);margin-bottom:0.5rem">خيارات معالجة الفروقات:</div>
        
        <form method="POST" action="{{ route('import.resolve_action', $batch) }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
            @csrf
            <input type="hidden" name="action" value="manual_upload">
            <input type="file" name="resolution_file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required style="max-width: 250px;">
            <button type="submit" class="btn-walim btn-sm" style="background:var(--card-bg);color:var(--text);border:1px solid var(--border)">
                <i class="bi bi-cloud-arrow-up"></i> رفع الإكسل المعدل
            </button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>Captain ID</th>
                    <th>Shift ID</th>
                    <th>الاسم</th>
                    <th>الطلبات</th>
                    <th>الإيراد</th>
                    <th>التسوية</th>
                    <th>السبب</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unresolvedRecords->take(50) as $record)
                <tr>
                    <td>{{ $record->record_date?->format('Y/m/d') }}</td>
                    <td><code style="color:var(--accent-light)">{{ $record->captain_id }}</code></td>
                    <td><code style="color:var(--text-muted)">{{ $record->shift_id }}</code></td>
                    <td>{{ $record->captain_name }}</td>
                    <td>{{ $record->orders }}</td>
                    <td>{{ number_format($record->suppliers_costs, 2) }}</td>
                    <td>{{ $record->adjustments != 0 ? number_format($record->adjustments, 2) : '—' }}</td>
                    <td><span class="status-badge badge-loss" style="font-size:0.7rem">لا توجد بيانات</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($unresolvedRecords->count() > 50)
        <div class="text-center py-2 text-muted" style="font-size:0.8rem">
            يُعرض أول 50 سجل من {{ $unresolvedRecords->count() }}
        </div>
        @endif
    </div>
</div>
@endif

{{-- السجلات المكررة --}}
@if($duplicateRecords->count() > 0)
<div class="chart-card fade-in">
    <div class="chart-header">
        <div class="chart-title" style="color:var(--warning)">🔁 سجلات مكررة</div>
        <span class="status-badge badge-neutral">{{ $duplicateRecords->count() }}</span>
    </div>
    <div class="p-3 mb-2" style="background:rgba(245,158,11,0.05); border-right:3px solid var(--warning); font-size:0.85rem; color:var(--text-muted)">
        <strong>مصدر هذه السجلات:</strong> تقرير التطبيق (الإكسل) الذي قمت برفعه.<br>
        <strong>السبب:</strong> التطبيق أصدر أكثر من سطر <strong>متطابق تماماً</strong> لنفس الكابتن في نفس اليوم (تطابق تام في الطلبات، الإيراد، والتسويات).<br>
        <em>يُرجى المراجعة للتأكد من عدم وجود نسخ ولصق خاطئ في ملف التطبيق كي لا تُحتسب المبالغ مضاعفة.</em>
    </div>
    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr><th>التاريخ</th><th>Captain ID</th><th>رقم الإقامة</th><th>الطلبات</th><th>الإيراد</th></tr>
            </thead>
            <tbody>
                @foreach($duplicateRecords->take(30) as $record)
                <tr>
                    <td>{{ $record->record_date?->format('Y/m/d') }}</td>
                    <td><code style="color:var(--warning)">{{ $record->captain_id }}</code></td>
                    <td>{{ $record->resolved_iqama ?? '—' }}</td>
                    <td>{{ $record->orders }}</td>
                    <td>{{ number_format($record->suppliers_costs, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
