@extends('layouts.app')
@section('title', 'تفاصيل المسير')
@section('page-title', 'مسير رواتب ' . $payroll->platform->name . ' — ' . \Carbon\Carbon::parse($payroll->month)->locale('ar')->translatedFormat('F Y'))

@section('content')

{{-- Header --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 fade-in">
    <div>
        <h6 class="mb-0 fw-bold">{{ $payroll->platform->name }} — {{ \Carbon\Carbon::parse($payroll->month)->locale('ar')->translatedFormat('F Y') }}</h6>
        <span class="status-badge {{ match($payroll->status) {'draft'=>'badge-neutral','calculated'=>'badge-done','approved'=>'badge-active','paid'=>'badge-profit',default=>'badge-neutral'} }}">
            {{ match($payroll->status) {'draft'=>'مسودة','calculated'=>'محسوب','approved'=>'معتمد','paid'=>'مصروف',default=>$payroll->status} }}
        </span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if(in_array($payroll->status, ['draft','calculated']))
        <form method="POST" action="{{ route('payroll.calculate', $payroll) }}">
            @csrf
            <button type="submit" class="btn-walim">
                <i class="bi bi-cpu"></i> احتساب الرواتب
            </button>
        </form>
        @endif
        @if($payroll->status === 'calculated')
        <form method="POST" action="{{ route('payroll.approve', $payroll) }}">
            @csrf
            <button type="submit" class="btn-walim" style="background:var(--success)">
                <i class="bi bi-check-circle"></i> اعتماد المسير
            </button>
        </form>
        @endif
        <a href="{{ route('payroll.export', $payroll) }}" class="btn-ghost">
            <i class="bi bi-file-earmark-excel"></i> تصدير إكسل
        </a>
        <button type="button" class="btn-ghost" data-bs-toggle="modal" data-bs-target="#printSlipsModal">
            <i class="bi bi-printer"></i> طباعة السندات
        </button>
        @if(in_array($payroll->status, ['draft','calculated']))
        <form method="POST" action="{{ route('payroll.destroy', $payroll) }}" onsubmit="return confirm('هل أنت متأكد من رغبتك في حذف مسير الرواتب هذا نهائياً؟ \n\nتنبيه: سيتم حذف المسير وجميع تفاصيله بشكل نهائي.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-ghost" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                <i class="bi bi-trash"></i> حذف المسير
            </button>
        </form>
        @endif
        <a href="{{ route('payroll.index') }}" class="btn-ghost">
            <i class="bi bi-arrow-right"></i> رجوع
        </a>
    </div>
</div>

{{-- ملخص المسير --}}
@if($totals)
<div class="row g-3 mb-4">
    @foreach([
        ['label'=>'عدد الموظفين','value'=>number_format($totals->drivers),'icon'=>'bi-people-fill','color'=>'kpi-blue','suffix'=>''],
        ['label'=>'إجمالي الطلبات','value'=>number_format($totals->orders),'icon'=>'bi-box-seam','color'=>'kpi-yellow','suffix'=>''],
        ['label'=>'إجمالي الإيرادات','value'=>number_format($totals->revenue,0),'icon'=>'bi-currency-dollar','color'=>'kpi-green','suffix'=>' ر.س'],
        ['label'=>'إجمالي الرواتب الصافية','value'=>number_format($totals->salary,0),'icon'=>'bi-cash-stack','color'=>'kpi-red','suffix'=>' ر.س'],
        ['label'=>'إجمالي المبالغ المتبقية','value'=>number_format($totals->remaining,0),'icon'=>'bi-wallet2','color'=>'kpi-blue','suffix'=>' ر.س'],
        ['label'=>'صافي الربح الإجمالي','value'=>number_format($totals->profit,0),'icon'=>'bi-graph-up-arrow','color'=>($totals->profit>=0?'kpi-green':'kpi-red'),'suffix'=>' ر.س'],
    ] as $i => $k)
    <div class="col-xl-2 col-md-4 col-6 fade-in fade-in-{{ $i+1 }}">
        <div class="kpi-card {{ $k['color'] }}" style="padding:1rem">
            <div class="kpi-icon" style="width:36px;height:36px;font-size:1rem;margin-bottom:0.5rem">
                <i class="bi {{ $k['icon'] }}"></i>
            </div>
            <div class="kpi-value" style="font-size:1.2rem">{{ $k['value'] }}{{ $k['suffix'] }}</div>
            <div class="kpi-label">{{ $k['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- جدول المسير --}}
<div class="chart-card fade-in">
    <div class="chart-header">
        <div class="chart-title">📋 تفاصيل الموظفين</div>
        <div class="d-flex gap-2">
            <input type="text" id="tableSearch" class="form-control form-control-dark"
                   style="width:200px" placeholder="ابحث...">
        </div>
    </div>

    @if($entries->isEmpty())
    <div class="text-center py-5" style="color:var(--text-muted)">
        <i class="bi bi-calculator" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>
        لم يتم احتساب الرواتب بعد. اضغط "احتساب الرواتب" أعلاه.
    </div>
    @else
    <div class="table-responsive">
        <table class="table walim-table" id="payrollTable">
            @php
                $isKeeta = $payroll->platform->settingsForMonth($payroll->month)?->isKeetaSlabs();
            @endphp
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الإقامة</th>
                    <th>الفرع</th>
                    <th>الطلبات</th>
                    @if(!$isKeeta)
                    <th>أيام العمل</th>
                    @endif
                    <th>الإيراد</th>
                    @if($isKeeta)
                    <th>التقييم</th>
                    @endif
                    <th>الراتب الأساسي</th>
                    @if($isKeeta)
                    <th>الحافز</th>
                    <th>البونص</th>
                    <th>إجمالي الراتب</th>
                    @else
                    <th>البونص</th>
                    @endif
                    <th>الخصومات</th>
                    <th>الصافي</th>
                    <th>الباقي</th>
                    <th>الربحية</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $entry->employee_id) }}" style="color:var(--text-primary);text-decoration:none">
                            {{ $entry->employee?->display_name ?? '—' }}
                        </a><br>
                        <small class="text-muted" title="أرقام الحسابات (IDs)">{{ $entry->id_numbers }}</small>
                    </td>
                    <td><code style="color:var(--accent-light);font-size:0.78rem">{{ $entry->iqama_number }}</code></td>
                    <td><small class="text-muted">{{ $entry->branch }}</small></td>
                    <td class="fw-bold">{{ number_format($entry->total_orders) }}</td>
                    @if(!$isKeeta)
                    <td>{{ $entry->working_days > 0 ? $entry->working_days : '—' }}</td>
                    @endif
                    <td>{{ number_format($entry->total_revenue, 0) }}</td>
                    @if($isKeeta)
                    <td>
                        @if($entry->grade)
                            <span class="badge" style="background:var(--kpi-blue); font-size:0.85rem;">{{ $entry->grade }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    @endif
                    <td>{{ number_format($entry->basic_salary, 0) }}</td>
                    
                    @if($isKeeta)
                    <td>
                        @if($entry->daily_target_excess > 0)
                        <span style="color:var(--success)">+{{ number_format($entry->daily_target_excess, 0) }}</span>
                        @else —
                        @endif
                    </td>
                    <td>
                        @if($entry->bonus > 0)
                        <span style="color:var(--success)">+{{ number_format($entry->bonus, 0) }}</span>
                        @else —
                        @endif
                    </td>
                    <td class="fw-bold text-primary">{{ number_format($entry->total_salary, 0) }}</td>
                    @else
                    <td>
                        @if($entry->bonus > 0)
                        <span style="color:var(--success)">+{{ number_format($entry->bonus, 0) }}</span>
                        @else —
                        @endif
                    </td>
                    @endif

                    <td>
                        @if($entry->total_deductions > 0)
                        <span style="color:var(--danger)">{{ number_format($entry->total_deductions, 0) }}</span>
                        @else —
                        @endif
                    </td>
                    <td class="fw-bold">{{ number_format($entry->net_salary, 0) }}</td>
                    <td class="fw-bold" style="color:var(--accent-light)">{{ number_format($entry->remaining_salary, 0) }}</td>
                    <td style="font-weight:700;color:{{ $entry->profit_loss >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                        {{ number_format($entry->profit_loss, 0) }}
                    </td>
                    <td>
                        <a href="{{ route('payroll.slip', [$payroll, $entry]) }}"
                           class="icon-btn" title="طباعة قسيمة" style="width:28px;height:28px;color:var(--warning)">
                            <i class="bi bi-printer" style="font-size:0.78rem"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3 d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $entries->firstItem() }}–{{ $entries->lastItem() }} من {{ $entries->total() }}</small>
        {{ $entries->links() }}
    </div>
    @endif
</div>

{{-- Modal للطباعة المجمعة --}}
<div class="modal fade" id="printSlipsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content chart-card p-0" style="border:1px solid var(--border)">
            <form action="{{ route('payroll.slips.batch', $payroll) }}" method="POST" target="_blank">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title chart-title">🖨️ طباعة سندات الصرف (إنجليزي)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:0.9rem">اختر الفلاتر لطباعة سندات الصرف في ملف PDF واحد. إذا لم تختر شيئاً، سيتم طباعة جميع السندات.</p>
                    
                    <div class="mb-3">
                        <label class="form-label-dark">الفرع (اختياري)</label>
                        <select name="branch_id" class="form-select form-select-dark">
                            <option value="">جميع الفروع</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-dark">تحديد موظفين (اختياري)</label>
                        <input type="text" name="employee_ids" class="form-control form-control-dark" placeholder="مثال: 1,2,5">
                        <small class="text-muted">أدخل أرقام ID الموظفين مفصولة بفاصلة لطباعة موظفين محددين فقط.</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn-ghost" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn-walim">
                        <i class="bi bi-printer"></i> استخراج PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('missing_salaries'))
<div class="modal fade" id="missingDataModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content chart-card" style="border:1px solid rgba(239, 68, 68, 0.3)">
            <div class="modal-header border-0">
                <h5 class="modal-title" style="color:var(--kpi-red)">⚠️ تنبيه: بيانات رواتب ناقصة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>تم العثور على الموظفين التاليين يعملون في هذه المنصة وليس لديهم <strong>راتب متفق عليه</strong> في ملفاتهم الشخصية.</p>
                <p>يقترح النظام اعتماد <strong>الراتب الافتراضي</strong> الموضح أمام كل موظف وحفظه في ملفه لتتمكن من احتساب المسير بشكل صحيح.</p>
                
                <div class="table-responsive mt-3 mb-4">
                    <table class="table walim-table">
                        <thead>
                            <tr>
                                <th>اسم الموظف</th>
                                <th>الإقامة</th>
                                <th>الراتب الافتراضي المقترح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('missing_salaries') as $missing)
                            <tr>
                                <td>{{ $missing['name'] }}</td>
                                <td><code style="color:var(--accent-light)">{{ $missing['iqama'] }}</code></td>
                                <td><strong style="color:var(--success)">{{ $missing['proposed_salary'] }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal">إلغاء والتعديل يدوياً</button>
                <form method="POST" action="{{ route('payroll.calculate', $payroll) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="apply_defaults" value="1">
                    @php
                        $defaults = [];
                        foreach(session('missing_salaries') as $m) {
                            $defaults[$m['id']] = $m['proposed_salary'];
                        }
                    @endphp
                    <input type="hidden" name="defaults_to_apply" value="{{ json_encode($defaults) }}">
                    <button type="submit" class="btn-walim" style="background:var(--success)">
                        <i class="bi bi-check-circle"></i> موافقة واعتماد الافتراضي
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// بحث في الجدول
document.getElementById('tableSearch')?.addEventListener('input', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('#payrollTable tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});

@if(session('missing_salaries'))
    document.addEventListener("DOMContentLoaded", function() {
        const missingModal = new bootstrap.Modal(document.getElementById('missingDataModal'));
        missingModal.show();
    });
@endif
</script>
@endpush
