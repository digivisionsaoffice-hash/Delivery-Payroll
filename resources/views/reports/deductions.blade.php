@extends('layouts.app')
@section('title', 'تقرير الخصومات')
@section('page-title', 'تقرير الخصومات والمديونيات')

@section('content')

{{-- فلاتر --}}
<div class="chart-card fade-in mb-4">
    <form method="GET" class="d-flex gap-3 align-items-end flex-wrap">
        <div>
            <label class="form-label-dark">الشهر</label>
            <input type="month" name="month" class="form-control form-control-dark" value="{{ $selectedMonth }}" style="width:160px">
        </div>
        <button type="submit" class="btn-walim"><i class="bi bi-filter"></i> تطبيق</button>
        <a href="{{ route('reports.index') }}" class="btn-ghost"><i class="bi bi-arrow-right"></i> رجوع</a>
    </form>
</div>

{{-- ملخص الخصومات --}}
<div class="row g-3 mb-4">
    @foreach([
        ['السلف النقدية', $summary['advances'], 'bi-cash-coin', 'kpi-blue'],
        ['الجزاءات', $summary['penalties'], 'bi-shield-x', 'kpi-red'],
        ['الصيانة', $summary['maintenance'], 'bi-tools', 'kpi-yellow'],
        ['قطع الغيار', $summary['spare_parts'], 'bi-gear-fill', 'kpi-yellow'],
        ['المخالفات المرورية', $summary['violations'], 'bi-sign-stop-fill', 'kpi-red'],
    ] as $i => [$l,$v,$ic,$c])
    <div class="col-xl fade-in fade-in-{{ $i+1 }}" style="min-width:150px">
        <div class="kpi-card {{ $c }}" style="padding:1rem">
            <div class="kpi-icon" style="width:36px;height:36px;font-size:0.95rem;margin-bottom:0.5rem"><i class="bi {{ $ic }}"></i></div>
            <div class="kpi-value" style="font-size:1.2rem">{{ number_format($v, 0) }}</div>
            <div class="kpi-label">{{ $l }} (ر.س)</div>
        </div>
    </div>
    @endforeach
    <div class="col-xl fade-in" style="min-width:150px">
        <div class="kpi-card kpi-red" style="padding:1rem; border:2px solid rgba(220,38,38,0.3)">
            <div class="kpi-icon" style="width:36px;height:36px;font-size:0.95rem;margin-bottom:0.5rem"><i class="bi bi-calculator-fill"></i></div>
            <div class="kpi-value" style="font-size:1.2rem;color:var(--danger)">{{ number_format($summary['total'], 0) }}</div>
            <div class="kpi-label">الإجمالي الكلي (ر.س)</div>
        </div>
    </div>
</div>

{{-- رسم بياني + جدول --}}
<div class="row g-3 mb-4">
    <div class="col-xl-4 fade-in">
        <div class="chart-card h-100">
            <div class="chart-header">
                <div class="chart-title">توزيع الخصومات حسب النوع</div>
            </div>
            <canvas id="dedPieChart" height="220"></canvas>
            <div class="mt-3" style="font-size:0.78rem">
                @foreach([
                    ['السلف', $summary['advances'], '#2563eb'],
                    ['الجزاءات', $summary['penalties'], '#dc2626'],
                    ['الصيانة', $summary['maintenance'], '#d97706'],
                    ['قطع الغيار', $summary['spare_parts'], '#7c3aed'],
                    ['المخالفات', $summary['violations'], '#0891b2'],
                ] as [$label, $val, $color])
                @if($val > 0)
                <div class="d-flex justify-content-between align-items-center py-1" style="border-bottom:1px solid var(--border-light)">
                    <span><span style="color:{{ $color }}">●</span> {{ $label }}</span>
                    <span class="fw-bold">{{ number_format($val, 0) }} ر.س</span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-xl-8 fade-in">
        <div class="chart-card h-100">
            <div class="chart-header">
                <div class="chart-title">أعلى الموظفين من حيث الخصومات</div>
                <div style="font-size:0.78rem;color:var(--text-muted)">{{ $selectedMonth }}</div>
            </div>
            <div class="table-responsive">
                <table class="table walim-table" id="dedTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الموظف</th>
                            <th>السلف</th>
                            <th>الجزاءات</th>
                            <th>الصيانة</th>
                            <th>قطع الغيار</th>
                            <th>المخالفات</th>
                            <th>الإجمالي</th>
                            <th>% من الراتب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byEmployee as $i => $row)
                        <tr @if($row['salary_ratio'] !== null && $row['salary_ratio'] >= 50) style="background:rgba(220,38,38,0.03)" @endif>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-bold" style="font-size:0.85rem">{{ $row['employee']->display_name }}</div>
                                <small class="text-muted">{{ $row['employee']->iqama_number }}</small>
                            </td>
                            <td>{{ $row['advances'] > 0 ? number_format($row['advances'], 0) : '—' }}</td>
                            <td>{{ $row['penalties'] > 0 ? number_format($row['penalties'], 0) : '—' }}</td>
                            <td>{{ $row['maintenance'] > 0 ? number_format($row['maintenance'], 0) : '—' }}</td>
                            <td>{{ $row['spare_parts'] > 0 ? number_format($row['spare_parts'], 0) : '—' }}</td>
                            <td>{{ $row['violations'] > 0 ? number_format($row['violations'], 0) : '—' }}</td>
                            <td><span class="fw-bold" style="color:var(--danger)">{{ number_format($row['total'], 0) }} ر.س</span></td>
                            <td>
                                @if($row['salary_ratio'] !== null)
                                    <span class="status-badge {{ $row['salary_ratio'] >= 50 ? 'badge-loss' : ($row['salary_ratio'] >= 25 ? 'badge-neutral' : 'badge-done') }}">
                                        {{ $row['salary_ratio'] }}%
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-4 text-muted">لا توجد خصومات لهذا الشهر</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const dedData = {
    labels: ['السلف', 'الجزاءات', 'الصيانة', 'قطع الغيار', 'المخالفات'],
    values: [{{ $summary['advances'] }}, {{ $summary['penalties'] }}, {{ $summary['maintenance'] }}, {{ $summary['spare_parts'] }}, {{ $summary['violations'] }}]
};
new Chart(document.getElementById('dedPieChart'), {
    type: 'doughnut',
    data: {
        labels: dedData.labels,
        datasets: [{ data: dedData.values, backgroundColor:['#2563eb','#dc2626','#d97706','#7c3aed','#0891b2'], borderColor:'#fff', borderWidth:3, hoverOffset:6 }]
    },
    options: { cutout:'65%', plugins: { legend:{display:false}, tooltip:{ backgroundColor:'#fff', borderColor:'#e2e8f0', borderWidth:1, titleColor:'#0f172a', bodyColor:'#334155', callbacks:{ label: c => c.label + ': ' + formatMoney(c.parsed) } } } }
});
$('#dedTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/2.0.8/i18n/ar.json' }, order:[[7,'desc']], pageLength:15 });
</script>
@endpush
