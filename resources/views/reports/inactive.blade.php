@extends('layouts.app')
@section('title', 'المناديب بدون نشاط')
@section('page-title', 'تقرير المناديب بدون نشاط')

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

{{-- إحصاء سريع --}}
<div class="row g-3 mb-4">
    <div class="col-md-6 fade-in fade-in-1">
        <div class="kpi-card kpi-red">
            <div class="kpi-icon"><i class="bi bi-person-x-fill"></i></div>
            <div class="kpi-value">{{ $stoppedDrivers->count() }}</div>
            <div class="kpi-label">مندوب متوقف عن العمل</div>
            <div class="kpi-trend down"><i class="bi bi-arrow-down-right"></i> توقف كلي مقارنة بالشهر السابق</div>
        </div>
    </div>
    <div class="col-md-6 fade-in fade-in-2">
        <div class="kpi-card kpi-yellow">
            <div class="kpi-icon"><i class="bi bi-arrow-down-circle-fill"></i></div>
            <div class="kpi-value">{{ $lowActivityDrivers->count() }}</div>
            <div class="kpi-label">مندوب منخفض النشاط (-40% فأكثر)</div>
            <div class="kpi-trend down"><i class="bi bi-arrow-down-right"></i> انخفاض ملحوظ في الأداء</div>
        </div>
    </div>
</div>

{{-- قسم المتوقفين --}}
<div class="chart-card fade-in mb-4">
    <div class="chart-header">
        <div>
            <div class="chart-title" style="color:var(--danger)"><i class="bi bi-person-x-fill me-1"></i> المناديب المتوقفون عن العمل</div>
            <div class="chart-subtitle">كانوا نشطين الشهر السابق ولم يظهروا في {{ $selectedMonth }}</div>
        </div>
        <span class="status-badge badge-loss">{{ $stoppedDrivers->count() }} مندوب</span>
    </div>
    @if($stoppedDrivers->count())
    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr>
                    <th>المندوب</th>
                    <th>الإقامة</th>
                    <th>طلبات الشهر السابق</th>
                    <th>إيراد الشهر السابق</th>
                    <th>أيام عمل السابق</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stoppedDrivers as $row)
                <tr>
                    <td>
                        <div class="fw-bold" style="font-size:0.85rem">{{ $row['employee']->display_name }}</div>
                        <small class="text-muted">{{ $row['employee']->city }}</small>
                    </td>
                    <td><code style="font-size:0.8rem;background:var(--bg-hover);padding:2px 6px;border-radius:4px">{{ $row['employee']->iqama_number }}</code></td>
                    <td><span class="fw-bold">{{ number_format($row['prev_orders']) }}</span></td>
                    <td>{{ number_format($row['prev_revenue'], 0) }} ر.س</td>
                    <td>{{ $row['prev_days'] }} يوم</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-4 text-muted">
        <i class="bi bi-check-circle-fill" style="font-size:2rem;color:var(--success);display:block;margin-bottom:0.5rem"></i>
        لا يوجد مناديب توقفوا عن العمل — أداء ممتاز!
    </div>
    @endif
</div>

{{-- قسم منخفضي النشاط --}}
<div class="chart-card fade-in">
    <div class="chart-header">
        <div>
            <div class="chart-title" style="color:var(--warning)"><i class="bi bi-arrow-down-circle-fill me-1"></i> المناديب منخفضو النشاط</div>
            <div class="chart-subtitle">انخفاض في الطلبات يزيد عن 40% مقارنة بالشهر السابق</div>
        </div>
        <span class="status-badge badge-neutral">{{ $lowActivityDrivers->count() }} مندوب</span>
    </div>
    @if($lowActivityDrivers->count())
    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr>
                    <th>المندوب</th>
                    <th>الطلبات الحالية</th>
                    <th>الطلبات السابقة</th>
                    <th>نسبة الانخفاض</th>
                    <th>الإيراد الحالي</th>
                    <th>الإيراد السابق</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowActivityDrivers as $row)
                <tr>
                    <td>
                        <div class="fw-bold" style="font-size:0.85rem">{{ $row['employee']->display_name }}</div>
                        <small class="text-muted">{{ $row['employee']->iqama_number }}</small>
                    </td>
                    <td><span class="fw-bold text-danger-custom">{{ number_format($row['curr_orders']) }}</span></td>
                    <td class="text-muted">{{ number_format($row['prev_orders']) }}</td>
                    <td>
                        <span class="status-badge {{ $row['drop_pct'] >= 70 ? 'badge-loss' : 'badge-neutral' }}">
                            <i class="bi bi-arrow-down-short"></i> {{ $row['drop_pct'] }}%
                        </span>
                    </td>
                    <td>{{ number_format($row['curr_revenue'], 0) }} ر.س</td>
                    <td class="text-muted">{{ number_format($row['prev_revenue'], 0) }} ر.س</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="text-center py-4 text-muted">
        <i class="bi bi-check-circle-fill" style="font-size:2rem;color:var(--success);display:block;margin-bottom:0.5rem"></i>
        لا يوجد مناديب منخفضو النشاط — الأداء مستقر!
    </div>
    @endif
</div>

@endsection
