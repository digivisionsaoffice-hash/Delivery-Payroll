@extends('layouts.app')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@push('styles')
<style>
.profit-bar { height: 6px; border-radius: 3px; background: var(--border); }
.profit-bar-fill { height: 100%; border-radius: 3px; transition: width 0.8s ease; }
</style>
@endpush

@section('content')

{{-- شهر المعلومات --}}
<div class="d-flex align-items-center justify-content-between mb-4 fade-in">
    <div>
        <h5 class="mb-0 fw-bold">مرحباً، {{ auth()->user()->name }} 👋</h5>
        <small class="text-muted">بيانات شهر {{ \Carbon\Carbon::parse($month)->locale('ar')->translatedFormat('F Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('import.index') }}" class="btn-walim">
            <i class="bi bi-cloud-upload"></i> استيراد بيانات
        </a>
        <a href="{{ route('payroll.index') }}" class="btn-ghost">
            <i class="bi bi-calculator"></i> مسير الرواتب
        </a>
    </div>
</div>

{{-- ===== KPI CARDS ===== --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 fade-in fade-in-1">
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="bi bi-currency-dollar"></i></div>
            <div class="kpi-value">{{ number_format($kpis['total_revenue'], 0) }}</div>
            <div class="kpi-label">إجمالي الإيرادات (ر.س)</div>
            <div class="kpi-trend {{ $revenueChange >= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $revenueChange >= 0 ? 'up' : 'down' }}-right"></i>
                {{ abs($revenueChange) }}% مقارنة بالشهر الماضي
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 fade-in fade-in-2">
        <div class="kpi-card kpi-red">
            <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="kpi-value">{{ number_format($kpis['total_salary'], 0) }}</div>
            <div class="kpi-label">إجمالي الرواتب (ر.س)</div>
            <div class="kpi-trend {{ $salaryChange <= 0 ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $salaryChange <= 0 ? 'down' : 'up' }}-right"></i>
                {{ abs($salaryChange) }}% مقارنة بالشهر الماضي
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 fade-in fade-in-3">
        <div class="kpi-card {{ $kpis['net_profit'] >= 0 ? 'kpi-green' : 'kpi-red' }}">
            <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="kpi-value" style="color: {{ $kpis['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)' }}">
                {{ number_format($kpis['net_profit'], 0) }}
            </div>
            <div class="kpi-label">صافي الربح (ر.س)</div>
            <div class="kpi-trend">
                <span class="status-badge {{ $kpis['profitable'] > $kpis['loss_drivers'] ? 'badge-profit' : 'badge-loss' }}">
                    {{ $kpis['profitable'] }} مربح / {{ $kpis['loss_drivers'] }} خسارة
                </span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 fade-in fade-in-4">
        <div class="kpi-card kpi-yellow">
            <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
            <div class="kpi-value">{{ $kpis['active_drivers'] }}</div>
            <div class="kpi-label">إجمالي الموظفين النشطين</div>
            <div class="kpi-trend">
                <i class="bi bi-box2-fill"></i>
                {{ number_format($kpis['total_orders']) }} طلبية هذا الشهر
            </div>
        </div>
    </div>
</div>

{{-- ===== CHARTS ROW ===== --}}
<div class="row g-3 mb-4">
    {{-- مخطط الربحية الشهري --}}
    <div class="col-xl-8 fade-in">
        <div class="chart-card h-100">
            <div class="chart-header">
                <div>
                    <div class="chart-title">الإيرادات والأرباح الشهرية</div>
                    <div class="chart-subtitle">آخر 6 أشهر</div>
                </div>
                <div class="d-flex gap-3" style="font-size:0.73rem;">
                    <span style="color:var(--accent)"><span style="font-size:1rem">●</span> الإيرادات</span>
                    <span style="color:var(--success)"><span style="font-size:1rem">●</span> الأرباح</span>
                    <span style="color:var(--danger)"><span style="font-size:1rem">●</span> الرواتب</span>
                </div>
            </div>
            <canvas id="profitChart" height="100"></canvas>
        </div>
    </div>

    {{-- توزيع المناديب --}}
    <div class="col-xl-4 fade-in">
        <div class="chart-card h-100">
            <div class="chart-header">
                <div>
                    <div class="chart-title">توزيع المناديب</div>
                    <div class="chart-subtitle">حسب الربحية</div>
                </div>
            </div>
            <canvas id="driverPieChart" height="200"></canvas>
            <div class="d-flex justify-content-center gap-3 mt-3" style="font-size:0.78rem;">
                <span style="color:var(--success)">● مربح ({{ $kpis['profitable'] }})</span>
                <span style="color:var(--danger)">● خسارة ({{ $kpis['loss_drivers'] }})</span>
                <span style="color:var(--warning)">● محايد</span>
            </div>
        </div>
    </div>
</div>

{{-- ===== TABLES ROW ===== --}}
<div class="row g-3">
    {{-- أفضل السائقين --}}
    <div class="col-xl-7 fade-in">
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <div class="chart-title">🏆 أفضل السائقين أداءً</div>
                    <div class="chart-subtitle">بناءً على صافي الربح هذا الشهر</div>
                </div>
                <a href="{{ route('profitability.index') }}" class="btn-ghost" style="font-size:0.8rem; padding:0.4rem 0.75rem;">
                    عرض الكل <i class="bi bi-arrow-left"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table walim-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الطلبات</th>
                            <th>الإيراد</th>
                            <th>صافي الربح</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topDrivers as $i => $entry)
                        <tr>
                            <td>
                                @if($i < 3)
                                    <span style="font-size:1.1rem">{{ ['🥇','🥈','🥉'][$i] }}</span>
                                @else
                                    <span class="text-muted">{{ $i + 1 }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-600" style="font-size:0.875rem;">{{ $entry->employee?->display_name ?? $entry->iqama_number }}</div>
                                <small class="text-muted">{{ $entry->branch }}</small>
                            </td>
                            <td><span class="fw-600">{{ number_format($entry->total_orders) }}</span></td>
                            <td>{{ number_format($entry->total_revenue, 0) }}</td>
                            <td style="color: {{ $entry->profit_loss >= 0 ? 'var(--success)' : 'var(--danger)' }}; font-weight:700;">
                                {{ number_format($entry->profit_loss, 0) }}
                            </td>
                            <td>
                                <span class="status-badge {{ $entry->profit_status === 'profit' ? 'badge-profit' : ($entry->profit_status === 'loss' ? 'badge-loss' : 'badge-neutral') }}">
                                    {{ $entry->profit_status === 'profit' ? 'مربح' : ($entry->profit_status === 'loss' ? 'خسارة' : 'محايد') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">لا توجد بيانات لهذا الشهر</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- العمود الجانبي --}}
    <div class="col-xl-5 d-flex flex-column gap-3 fade-in">

        {{-- مناديب يحتاجون مراجعة --}}
        @if($lossDrivers->count() > 0)
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <div class="chart-title" style="color:var(--danger)">⚠️ مناديب بخسارة</div>
                    <div class="chart-subtitle">يُنصح بمراجعة ملفاتهم</div>
                </div>
            </div>
            @foreach($lossDrivers as $entry)
            <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-size:0.875rem; font-weight:600;">{{ $entry->employee?->display_name ?? $entry->iqama_number }}</div>
                    <small class="text-muted">{{ $entry->total_orders }} طلبية</small>
                </div>
                <div style="color:var(--danger); font-weight:700; font-size:0.875rem;">
                    {{ number_format($entry->profit_loss, 0) }} ر.س
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- آخر مسيرات الرواتب --}}
        <div class="chart-card flex-1">
            <div class="chart-header">
                <div class="chart-title">📋 آخر مسيرات الرواتب</div>
            </div>
            @forelse($recentPayrolls as $period)
            <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-size:0.875rem; font-weight:600;">{{ $period->platform->name }}</div>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($period->month)->locale('ar')->translatedFormat('F Y') }}</small>
                </div>
                <span class="status-badge {{ match($period->status) { 'approved','paid' => 'badge-profit', 'calculated' => 'badge-done', default => 'badge-pending' } }}">
                    {{ match($period->status) { 'draft' => 'مسودة', 'calculated' => 'محسوب', 'approved' => 'معتمد', 'paid' => 'مصروف' } }}
                </span>
            </div>
            @empty
            <p class="text-muted text-center py-3">لا توجد مسيرات بعد</p>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ===== مخطط الربحية الشهري =====
const profitData = @json($profitTrend);

new Chart(document.getElementById('profitChart'), {
    type: 'line',
    data: {
        labels: profitData.map(d => d.month),
        datasets: [
            {
                label: 'الإيرادات',
                data: profitData.map(d => d.revenue),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.06)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: 'white',
                pointBorderWidth: 2,
            },
            {
                label: 'الأرباح',
                data: profitData.map(d => d.profit),
                borderColor: '#059669',
                backgroundColor: 'rgba(5,150,105,0.06)',
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#059669',
                pointBorderColor: 'white',
                pointBorderWidth: 2,
            },
            {
                label: 'الرواتب',
                data: profitData.map(d => d.salary),
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220,38,38,0.04)',
                fill: false,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3,
                borderDash: [5,5],
                pointBackgroundColor: '#dc2626',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#ffffff',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                titleColor: '#0f172a',
                bodyColor: '#334155',
                boxShadow: '0 4px 16px rgba(0,0,0,0.1)',
                callbacks: {
                    label: ctx => ctx.dataset.label + ': ' + formatMoney(ctx.parsed.y)
                }
            }
        },
        scales: {
            x: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b' } },
            y: {
                grid: { color: '#f1f5f9' },
                ticks: {
                    color: '#64748b',
                    callback: v => (v/1000).toFixed(0) + 'k'
                }
            }
        }
    }
});

// ===== مخطط توزيع المناديب =====
const profitable = {{ $kpis['profitable'] }};
const lossCount  = {{ $kpis['loss_drivers'] }};
const neutral    = Math.max(0, {{ $kpis['active_drivers'] }} - profitable - lossCount);

new Chart(document.getElementById('driverPieChart'), {
    type: 'doughnut',
    data: {
        labels: ['مربح', 'خسارة', 'محايد'],
        datasets: [{
            data: [profitable, lossCount, neutral],
            backgroundColor: ['#059669', '#dc2626', '#d97706'],
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 6
        }]
    },
    options: {
        cutout: '72%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#ffffff',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                titleColor: '#0f172a',
                bodyColor: '#334155',
            }
        }
    }
});
</script>
@endpush
