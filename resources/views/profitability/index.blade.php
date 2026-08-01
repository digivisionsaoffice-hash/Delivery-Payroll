@extends('layouts.app')
@section('title','تحليل الربحية')
@section('page-title','تحليل الربحية والأداء')
@section('content')

{{-- فلاتر --}}
<div class="chart-card fade-in mb-4">
    <form method="GET" class="d-flex gap-3 align-items-end flex-wrap">
        <div>
            <label class="form-label-dark">الشهر</label>
            <input type="month" name="month" class="form-control form-control-dark" value="{{ $selectedMonth }}">
        </div>
        <div>
            <label class="form-label-dark">المنصة</label>
            <select name="platform_id" class="form-select form-select-dark" style="width:160px">
                <option value="">كل المنصات</option>
                @foreach($platforms as $p)
                <option value="{{ $p->id }}" @selected($selectedPlatform == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-walim">
            <i class="bi bi-filter"></i> تطبيق
        </button>
    </form>
</div>

{{-- ملخص --}}
@if($totals)
<div class="row g-3 mb-4">
    @foreach([
        ['الإيرادات الكلية', number_format($totals->total_revenue,0).' ر.س', 'bi-currency-dollar','kpi-blue'],
        ['رواتب الكلية', number_format($totals->total_salary,0).' ر.س', 'bi-cash-stack','kpi-red'],
        ['صافي الربح', number_format($totals->total_profit,0).' ر.س', 'bi-graph-up','kpi-green'],
        ['مناديب مربحون', $totals->profitable.' / '.$totals->drivers, 'bi-person-check-fill','kpi-yellow'],
    ] as $i => [$l,$v,$ic,$c])
    <div class="col-xl-3 col-md-6 fade-in fade-in-{{ $i+1 }}">
        <div class="kpi-card {{ $c }}" style="padding:1rem">
            <div class="kpi-icon" style="width:36px;height:36px;font-size:1rem;margin-bottom:0.5rem"><i class="bi {{ $ic }}"></i></div>
            <div class="kpi-value" style="font-size:1.3rem">{{ $v }}</div>
            <div class="kpi-label">{{ $l }}</div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- جدول --}}
<div class="chart-card fade-in">
    <div class="chart-header">
        <div class="chart-title">📊 ربحية المناديب — {{ $selectedMonth }}</div>
        <div style="font-size:0.78rem;color:var(--text-muted)">مرتّب من الأقل ربحية</div>
    </div>
    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr><th>الموظف</th><th>المنصة</th><th>الطلبات</th><th>أيام العمل</th><th>الإيراد</th><th>الراتب الصافي</th><th>الربحية</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $entry->employee?->display_name ?? $entry->iqama_number }}</div>
                        <small class="text-muted">{{ $entry->branch ?: $entry->city }}</small>
                    </td>
                    <td><small>{{ $entry->payrollPeriod?->platform?->name }}</small></td>
                    <td>{{ number_format($entry->total_orders) }}</td>
                    <td>{{ $entry->working_days }}</td>
                    <td>{{ number_format($entry->total_revenue, 0) }}</td>
                    <td>{{ number_format($entry->net_salary, 0) }}</td>
                    <td style="font-weight:700;color:{{ $entry->profit_loss >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                        {{ $entry->profit_loss >= 0 ? '+' : '' }}{{ number_format($entry->profit_loss, 0) }}
                    </td>
                    <td>
                        <span class="status-badge {{ $entry->profit_loss >= 0 ? 'badge-profit' : 'badge-loss' }}">
                            {{ $entry->profit_loss >= 0 ? 'مربح' : 'خسارة' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('profitability.driver', $entry->employee_id) }}" class="icon-btn" style="width:28px;height:28px">
                            <i class="bi bi-person-lines-fill" style="font-size:0.78rem"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">لا توجد بيانات للشهر المختار</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $entries->links() }}</div>
</div>
@endsection
