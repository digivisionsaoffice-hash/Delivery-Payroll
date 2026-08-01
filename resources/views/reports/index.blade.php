@extends('layouts.app')
@section('title', 'التقارير')
@section('page-title', 'مركز التقارير')

@section('content')

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between mb-4 fade-in">
    <div>
        <h5 class="mb-0 fw-bold" style="color:var(--text-primary)">مركز التقارير التحليلية</h5>
        <small class="text-muted">اطلع على تقارير مفصلة وتحليلات دقيقة لأداء منظومتك</small>
    </div>
</div>

{{-- إحصاء سريع --}}
<div class="row g-3 mb-4">
    @foreach([
        ['إجمالي الموظفين', $stats['total_employees'], 'bi-people-fill', 'kpi-blue'],
        ['دورات الرواتب', $stats['total_periods'], 'bi-calendar-check', 'kpi-green'],
        ['سجلات الأرباح', $stats['total_entries'], 'bi-file-earmark-bar-graph', 'kpi-yellow'],
    ] as $i => [$label, $val, $icon, $color])
    <div class="col-md-4 fade-in fade-in-{{ $i+1 }}">
        <div class="kpi-card {{ $color }}" style="padding:1.1rem">
            <div class="kpi-icon" style="width:38px;height:38px;font-size:1rem;margin-bottom:0.6rem">
                <i class="bi {{ $icon }}"></i>
            </div>
            <div class="kpi-value" style="font-size:1.5rem">{{ number_format($val) }}</div>
            <div class="kpi-label">{{ $label }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- بطاقات التقارير --}}
<div class="row g-3">

    {{-- تقرير الأداء المقارن --}}
    <div class="col-xl-4 col-md-6 fade-in fade-in-1">
        <a href="{{ route('reports.performance') }}" style="text-decoration:none">
        <div class="chart-card h-100" style="cursor:pointer; transition: all 0.2s; border: 1.5px solid transparent;"
             onmouseover="this.style.borderColor='var(--accent)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--shadow-md)'"
             onmouseout="this.style.borderColor='transparent'; this.style.transform=''; this.style.boxShadow=''">
            <div style="width:46px;height:46px;border-radius:12px;background:var(--accent-soft);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem">
                <i class="bi bi-bar-chart-line-fill"></i>
            </div>
            <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin-bottom:0.3rem">تقرير الأداء المقارن</div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin:0 0 1rem">
                مقارنة شهر بشهر للإيرادات والطلبات مع رسوم بيانية تفاعلية لآخر 6 أشهر
            </p>
            <span class="status-badge badge-done">📊 مقارنة شهرية</span>
        </div>
        </a>
    </div>

    {{-- تقرير الخصومات --}}
    <div class="col-xl-4 col-md-6 fade-in fade-in-2">
        <a href="{{ route('reports.deductions') }}" style="text-decoration:none">
        <div class="chart-card h-100" style="cursor:pointer; transition: all 0.2s; border: 1.5px solid transparent;"
             onmouseover="this.style.borderColor='var(--warning)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--shadow-md)'"
             onmouseout="this.style.borderColor='transparent'; this.style.transform=''; this.style.boxShadow=''">
            <div style="width:46px;height:46px;border-radius:12px;background:var(--warning-soft);color:var(--warning);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem">
                <i class="bi bi-wallet2"></i>
            </div>
            <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin-bottom:0.3rem">تقرير الخصومات والمديونيات</div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin:0 0 1rem">
                ملخص شامل لجميع أنواع الخصومات (سلف، جزاءات، صيانة، مخالفات) مع تنبيهات ذكية
            </p>
            <span class="status-badge badge-neutral">💰 مالي شامل</span>
        </div>
        </a>
    </div>

    {{-- تقرير المناديب غير النشطين --}}
    <div class="col-xl-4 col-md-6 fade-in fade-in-3">
        <a href="{{ route('reports.inactive') }}" style="text-decoration:none">
        <div class="chart-card h-100" style="cursor:pointer; transition: all 0.2s; border: 1.5px solid transparent;"
             onmouseover="this.style.borderColor='var(--danger)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--shadow-md)'"
             onmouseout="this.style.borderColor='transparent'; this.style.transform=''; this.style.boxShadow=''">
            <div style="width:46px;height:46px;border-radius:12px;background:var(--danger-soft);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem">
                <i class="bi bi-person-dash-fill"></i>
            </div>
            <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin-bottom:0.3rem">تقرير المناديب بدون نشاط</div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin:0 0 1rem">
                كشف المناديب المتوقفين عن العمل أو الذين انخفض أداؤهم بشكل ملحوظ هذا الشهر
            </p>
            <span class="status-badge badge-loss">⚠️ تنبيهات نشاط</span>
        </div>
        </a>
    </div>

    {{-- تقرير الشذوذات --}}
    <div class="col-xl-4 col-md-6 fade-in fade-in-4">
        <a href="{{ route('reports.anomalies') }}" style="text-decoration:none">
        <div class="chart-card h-100" style="cursor:pointer; transition: all 0.2s; border: 1.5px solid transparent;"
             onmouseover="this.style.borderColor='var(--info)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--shadow-md)'"
             onmouseout="this.style.borderColor='transparent'; this.style.transform=''; this.style.boxShadow=''">
            <div style="width:46px;height:46px;border-radius:12px;background:var(--info-soft);color:var(--info);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin-bottom:0.3rem">تقرير الشذوذات والتكرار</div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin:0 0 1rem">
                كشف الـ IDs المكررة والمناديب الذين عملوا بأكثر من معرف في نفس اليوم
            </p>
            <span class="status-badge badge-done">🔍 تدقيق البيانات</span>
        </div>
        </a>
    </div>

    {{-- تقرير الربحية --}}
    <div class="col-xl-4 col-md-6 fade-in fade-in-1">
        <a href="{{ route('profitability.index') }}" style="text-decoration:none">
        <div class="chart-card h-100" style="cursor:pointer; transition: all 0.2s; border: 1.5px solid transparent;"
             onmouseover="this.style.borderColor='var(--success)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--shadow-md)'"
             onmouseout="this.style.borderColor='transparent'; this.style.transform=''; this.style.boxShadow=''">
            <div style="width:46px;height:46px;border-radius:12px;background:var(--success-soft);color:var(--success);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin-bottom:1rem">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary);margin-bottom:0.3rem">تحليل الربحية التفصيلي</div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin:0 0 1rem">
                ربحية كل منادوب مع الإيراد والتكلفة وهامش الربح، قابل للتصفية حسب المنصة والمدينة
            </p>
            <span class="status-badge badge-profit">📈 تحليل ربحي</span>
        </div>
        </a>
    </div>

</div>

@endsection
