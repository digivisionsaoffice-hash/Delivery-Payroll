@extends('layouts.app')
@section('title', 'تقرير الأداء المقارن')
@section('page-title', 'تقرير الأداء الشهري المقارن')

@push('styles')
<style>
.change-up   { color: var(--success); font-weight: 600; font-size: 0.75rem; }
.change-down { color: var(--danger);  font-weight: 600; font-size: 0.75rem; }
.change-null { color: var(--text-light); font-size: 0.75rem; }
.trend-bar   { height: 4px; border-radius: 2px; background: var(--border); overflow: hidden; }
.trend-fill  { height: 100%; border-radius: 2px; transition: width 1s ease; }
</style>
@endpush

@section('content')

{{-- فلاتر --}}
<div class="chart-card fade-in mb-4">
    <form method="GET" class="d-flex gap-3 align-items-end flex-wrap">
        <div>
            <label class="form-label-dark">الشهر الحالي</label>
            <input type="month" name="month" class="form-control form-control-dark" value="{{ $selectedMonth }}" style="width:160px">
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
        <button type="submit" class="btn-walim"><i class="bi bi-filter"></i> تطبيق</button>
        <a href="{{ route('reports.index') }}" class="btn-ghost"><i class="bi bi-arrow-right"></i> رجوع</a>
    </form>
</div>

{{-- ملخص المقارنة --}}
<div class="row g-3 mb-4">
    @php
        $revChange = $summary['prev_revenue'] > 0 ? round((($summary['current_revenue'] - $summary['prev_revenue']) / $summary['prev_revenue']) * 100, 1) : null;
        $ordChange = $summary['prev_orders'] > 0 ? round((($summary['current_orders'] - $summary['prev_orders']) / $summary['prev_orders']) * 100, 1) : null;
    @endphp
    @foreach([
        ['الإيرادات الكلية', number_format($summary['current_revenue'],0).' ر.س', $revChange, 'kpi-blue', 'bi-currency-dollar'],
        ['إجمالي الطلبات', number_format($summary['current_orders']), $ordChange, 'kpi-yellow', 'bi-box-seam'],
        ['إجمالي الرواتب', number_format($summary['current_salary'],0).' ر.س', null, 'kpi-red', 'bi-cash-stack'],
        ['عدد المناديب', $summary['current_count'], null, 'kpi-green', 'bi-people-fill'],
    ] as $i => [$l,$v,$chg,$c,$ic])
    <div class="col-xl-3 col-md-6 fade-in fade-in-{{ $i+1 }}">
        <div class="kpi-card {{ $c }}">
            <div class="kpi-icon"><i class="bi {{ $ic }}"></i></div>
            <div class="kpi-value">{{ $v }}</div>
            <div class="kpi-label">{{ $l }}</div>
            @if($chg !== null)
            <div class="kpi-trend {{ $chg >= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $chg >= 0 ? 'up' : 'down' }}-right"></i>
                {{ abs($chg) }}% مقارنة بالشهر السابق
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- رسم بياني: آخر 6 أشهر --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8 fade-in">
        <div class="chart-card h-100">
            <div class="chart-header">
                <div>
                    <div class="chart-title">اتجاه الأداء — آخر 6 أشهر</div>
                    <div class="chart-subtitle">الإيرادات والرواتب والأرباح</div>
                </div>
                <div class="d-flex gap-3" style="font-size:0.73rem">
                    <span style="color:var(--accent)">● الإيرادات</span>
                    <span style="color:var(--success)">● الأرباح</span>
                    <span style="color:var(--danger)">● الرواتب</span>
                </div>
            </div>
            <canvas id="trendChart" height="110"></canvas>
        </div>
    </div>
    <div class="col-xl-4 fade-in">
        <div class="chart-card h-100">
            <div class="chart-header">
                <div class="chart-title">توزيع الشهر الحالي</div>
            </div>
            <canvas id="distChart" height="200"></canvas>
            <div class="mt-3" style="font-size:0.78rem">
                <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid var(--border-light)">
                    <span class="text-muted">إجمالي الإيرادات</span>
                    <span class="fw-bold">{{ number_format($summary['current_revenue'],0) }} ر.س</span>
                </div>
                <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid var(--border-light)">
                    <span class="text-muted">إجمالي الرواتب</span>
                    <span class="fw-bold" style="color:var(--danger)">{{ number_format($summary['current_salary'],0) }} ر.س</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">هامش الربح</span>
                    @php $margin = $summary['current_revenue'] > 0 ? round((($summary['current_revenue'] - $summary['current_salary']) / $summary['current_revenue']) * 100, 1) : 0; @endphp
                    <span class="fw-bold" style="color:{{ $margin >= 0 ? 'var(--success)' : 'var(--danger)' }}">{{ $margin }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- جدول المقارنة --}}
<div class="chart-card fade-in">
    <div class="chart-header">
        <div class="chart-title">مقارنة أداء المناديب</div>
        <div style="font-size:0.78rem;color:var(--text-muted)">{{ $selectedMonth }} مقارنةً بالشهر السابق</div>
    </div>
    <div class="table-responsive">
        <table class="table walim-table" id="perfTable">
            <thead>
                <tr>
                    <th>المندوب</th>
                    <th>الطلبات الحالية</th>
                    <th>الطلبات السابقة</th>
                    <th>التغير</th>
                    <th>الإيراد الحالي</th>
                    <th>الإيراد السابق</th>
                    <th>التغير %</th>
                    <th>أيام العمل</th>
                    <th>الربحية</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comparison as $row)
                @php $entry = $row['entry']; @endphp
                <tr>
                    <td>
                        <div class="fw-bold" style="font-size:0.85rem">{{ $entry->employee?->display_name ?? $entry->iqama_number }}</div>
                        <small class="text-muted">{{ $entry->city }}</small>
                    </td>
                    <td class="fw-bold">{{ number_format($entry->total_orders) }}</td>
                    <td class="text-muted">{{ number_format($row['prev_orders']) }}</td>
                    <td>
                        @if($row['orders_change'] !== null)
                            <span class="{{ $row['orders_change'] >= 0 ? 'change-up' : 'change-down' }}">
                                <i class="bi bi-arrow-{{ $row['orders_change'] >= 0 ? 'up' : 'down' }}-short"></i>
                                {{ abs($row['orders_change']) }}%
                            </span>
                        @else <span class="change-null">—</span>
                        @endif
                    </td>
                    <td>{{ number_format($entry->total_revenue, 0) }}</td>
                    <td class="text-muted">{{ number_format($row['prev_revenue'], 0) }}</td>
                    <td>
                        @if($row['revenue_change'] !== null)
                            <span class="{{ $row['revenue_change'] >= 0 ? 'change-up' : 'change-down' }}">
                                {{ $row['revenue_change'] >= 0 ? '+' : '' }}{{ $row['revenue_change'] }}%
                            </span>
                        @else <span class="change-null">جديد</span>
                        @endif
                    </td>
                    <td>{{ $entry->working_days }}</td>
                    <td>
                        <span class="status-badge {{ $entry->profit_loss >= 0 ? 'badge-profit' : 'badge-loss' }}">
                            {{ $entry->profit_loss >= 0 ? '+' : '' }}{{ number_format($entry->profit_loss, 0) }} ر.س
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">لا توجد بيانات لهذا الشهر</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
const trend = @json($trendData->values());

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trend.map(d => d.month),
        datasets: [
            { label: 'الإيرادات', data: trend.map(d => d.revenue), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.05)', fill:true, tension:0.4, borderWidth:2.5, pointRadius:4, pointBackgroundColor:'#2563eb', pointBorderColor:'white', pointBorderWidth:2 },
            { label: 'الأرباح',   data: trend.map(d => d.profit),  borderColor: '#059669', backgroundColor: 'rgba(5,150,105,0.05)',  fill:true, tension:0.4, borderWidth:2.5, pointRadius:4, pointBackgroundColor:'#059669', pointBorderColor:'white', pointBorderWidth:2 },
            { label: 'الرواتب',  data: trend.map(d => d.salary),  borderColor: '#dc2626', fill:false, tension:0.4, borderWidth:2, borderDash:[5,5], pointRadius:3, pointBackgroundColor:'#dc2626' },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: {display:false}, tooltip: { backgroundColor:'#fff', borderColor:'#e2e8f0', borderWidth:1, titleColor:'#0f172a', bodyColor:'#334155', callbacks: { label: c => c.dataset.label + ': ' + formatMoney(c.parsed.y) } } },
        scales: { x: { grid:{color:'#f1f5f9'}, ticks:{color:'#64748b'} }, y: { grid:{color:'#f1f5f9'}, ticks:{color:'#64748b', callback: v => (v/1000).toFixed(0)+'k'} } }
    }
});

new Chart(document.getElementById('distChart'), {
    type: 'doughnut',
    data: {
        labels: ['الرواتب', 'صافي الربح'],
        datasets: [{ data: [{{ $summary['current_salary'] }}, {{ max(0, $summary['current_revenue'] - $summary['current_salary']) }}], backgroundColor: ['#dc2626','#059669'], borderColor:'#fff', borderWidth:3, hoverOffset:6 }]
    },
    options: { cutout:'70%', plugins: { legend:{display:false}, tooltip:{ backgroundColor:'#fff', borderColor:'#e2e8f0', borderWidth:1, titleColor:'#0f172a', bodyColor:'#334155' } } }
});

$('#perfTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/2.0.8/i18n/ar.json' }, order:[[4,'desc']], pageLength:25 });
</script>
@endpush
