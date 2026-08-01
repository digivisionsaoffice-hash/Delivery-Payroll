@extends('layouts.app')
@section('title', 'معالجة البيانات')
@section('page-title', 'معالجة البيانات وسجل الاستيرادات')

@section('content')

<div class="row g-3">
    <div class="col-12">
        <div class="chart-card fade-in">
            <div class="chart-title mb-3">📜 سجل الاستيرادات والمعالجة</div>

            @if($recentBatches->isEmpty())
            <div class="text-center py-5" style="color:var(--text-muted)">
                <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;opacity:0.4"></i>
                لم يتم رفع أي ملفات بعد لتتم معالجتها.
            </div>
            @else
            <div class="table-responsive">
                <table class="table walim-table">
                    <thead>
                        <tr>
                            <th>المنصة</th>
                            <th>الشهر</th>
                            <th>النوع</th>
                            <th>السجلات</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBatches as $batch)
                        <tr>
                            <td><span style="font-weight:600">{{ $batch->platform->name ?? 'عام' }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($batch->month)->format('Y/m') }}</td>
                            <td>
                                @php $label = \App\Support\PlatformColumnMap::label($batch->sheet_type); @endphp
                                <span class="status-badge {{ $batch->sheet_type === 'app_report' ? 'badge-active' : 'badge-neutral' }}" style="font-size:0.7rem">
                                    {{ $label }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold">{{ number_format($batch->rows_imported) }}</span>
                                @if($batch->rows_failed > 0)
                                <div class="mt-1 d-flex align-items-center gap-1">
                                    <small class="text-danger">{{ $batch->rows_failed }} خطأ</small>
                                    <a href="{{ route('import.export_errors', $batch->id) }}" class="btn-ghost" style="padding:0.1rem 0.3rem;font-size:0.68rem;color:#ef4444;border:1px solid #ef4444" title="تنزيل الأسطر الخاطئة">
                                        <i class="bi bi-download"></i> تنزيل
                                    </a>
                                </div>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge {{ match($batch->status) {
                                    'done' => 'badge-active', 'processing' => 'badge-pending',
                                    'failed' => 'badge-loss', default => 'badge-neutral'
                                } }}">
                                    {{ match($batch->status) {
                                        'done' => 'تم', 'processing' => 'جاري',
                                        'failed' => 'فشل', default => 'انتظار'
                                    } }}
                                </span>

                                {{-- تحذير الأعمدة غير المعروفة --}}
                                @if(!empty($batch->unknown_columns) && count($batch->unknown_columns) > 0)
                                <button type="button" class="btn-ghost ms-1" style="padding:0.1rem 0.4rem;font-size:0.68rem;border-color:#f59e0b;color:#f59e0b"
                                        onclick="showUnknownCols({{ json_encode($batch->unknown_columns) }})">
                                    <i class="bi bi-exclamation-triangle"></i> أعمدة جديدة
                                </button>
                                @endif

                                @if(!empty($batch->errors) && count($batch->errors) > 0)
                                <button type="button" class="btn-ghost ms-1" style="padding:0.1rem 0.4rem;font-size:0.68rem"
                                        onclick="showErrors({{ $batch->id }}, {{ json_encode($batch->errors) }})">
                                    <i class="bi bi-x-circle text-danger"></i>
                                </button>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($batch->sheet_type === 'app_report' && $batch->status === 'done')
                                    <form method="POST" action="{{ route('import.process', $batch) }}">
                                        @csrf
                                        <button type="submit" class="btn-walim" style="padding:0.3rem 0.6rem;font-size:0.75rem" title="معالجة الإقامات">
                                            <i class="bi bi-cpu"></i> معالجة
                                        </button>
                                    </form>
                                    <a href="{{ route('import.reconciliation', $batch) }}" class="btn-ghost" style="padding:0.3rem 0.6rem;font-size:0.75rem" title="مطابقة التسويات">
                                        <i class="bi bi-search"></i> مطابقة
                                    </a>
                                    @endif

                                    @if($batch->status === 'done')
                                    <a href="{{ route('import.records', $batch) }}" class="btn-ghost text-info" style="padding:0.3rem 0.6rem;font-size:0.75rem" title="عرض البيانات">
                                        <i class="bi bi-table"></i>
                                    </a>
                                    @endif

                                    <form method="POST" action="{{ route('import.destroy', $batch) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost text-danger" style="padding:0.3rem 0.6rem;font-size:0.75rem" title="حذف السجل">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal: أعمدة غير معروفة --}}
<div id="unknownColsModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-primary)">
            <div class="modal-header" style="border-color:#f59e0b30;background:rgba(245,158,11,0.08)">
                <h5 class="modal-title" style="color:#f59e0b">⚠️ أعمدة لم تُدرَج في الحسبة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:0.75rem">
                    الأعمدة التالية موجودة في الملف لكنها ليست جزءاً من حسبة الرواتب الحالية.
                    إذا كانت تحتوي على بيانات مهمة (كغرامات أو محافظ)، يُنصح بمراجعتها.
                </p>
                <div id="unknownColsList"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: أخطاء الاستيراد --}}
<div id="errorsModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);color:var(--text-primary)">
            <div class="modal-header" style="border-color:var(--border)">
                <h5 class="modal-title">⚠️ أخطاء وملاحظات الاستيراد</h5>
                <div class="d-flex align-items-center gap-2">
                    <a id="downloadErrorsBtn" href="#" class="btn btn-sm btn-outline-danger" style="display:none; font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                        <i class="bi bi-download"></i> تنزيل السطور
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" id="errorsBody" style="font-size:0.82rem;font-family:monospace"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showUnknownCols(cols) {
    const list = document.getElementById('unknownColsList');
    list.innerHTML = cols.map(c =>
        `<div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:6px;padding:0.4rem 0.75rem;margin-bottom:0.4rem;font-family:monospace;color:#fbbf24">
            📌 ${c}
        </div>`
    ).join('');
    new bootstrap.Modal(document.getElementById('unknownColsModal')).show();
}

function showErrors(batchId, errors) {
    const body = document.getElementById('errorsBody');
    body.innerHTML = errors.map((e, i) => {
        let isWarning = e.message && (e.message.includes('تنبيه') || e.message.includes('تحذير'));
        let bg = isWarning ? 'rgba(245,158,11,0.08)' : 'rgba(239,68,68,0.08)';
        let title = isWarning ? 'ملاحظة' : 'خطأ';
        let icon = isWarning ? 'bi-exclamation-triangle text-warning' : 'bi-x-circle text-danger';
        return `<div style="margin-bottom:0.4rem;padding:0.4rem;background:${bg};border-radius:6px">
            <i class="bi ${icon} me-1"></i> <strong>${title} ${i+1}:</strong> ${e.message || JSON.stringify(e)}
        </div>`;
    }).join('');
    
    const dlBtn = document.getElementById('downloadErrorsBtn');
    if (batchId && errors.some(e => e.row)) {
        dlBtn.href = `/import/${batchId}/export-errors`;
        dlBtn.style.display = 'inline-block';
    } else {
        dlBtn.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('errorsModal')).show();
}
</script>
@endpush
