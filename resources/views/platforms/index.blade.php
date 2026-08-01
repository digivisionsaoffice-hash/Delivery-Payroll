@extends('layouts.app')
@section('title', 'منصات التوصيل')
@section('page-title', 'منصات التوصيل')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div class="chart-title">المنصات المرتبطة بالشركة</div>
    <a href="{{ route('platforms.create') }}" class="btn-walim"><i class="bi bi-plus-lg"></i> إضافة منصة</a>
</div>

<div class="row g-3">
    @forelse($platforms as $platform)
    <div class="col-xl-4 col-md-6 fade-in">
        <div class="chart-card h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:52px;height:52px;background:var(--gradient-1);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">
                    🚀
                </div>
                <div>
                    <div class="fw-bold" style="font-size:1.1rem">{{ $platform->name }}</div>
                    <div class="text-muted" style="font-size:0.8rem">{{ $platform->name_en }}</div>
                </div>
                <div class="me-auto">
                    <span class="status-badge {{ $platform->is_active ? 'badge-active' : 'badge-loss' }}">
                        {{ $platform->is_active ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div style="background:var(--bg-hover);border-radius:8px;padding:0.6rem 0.8rem;text-align:center">
                        <div class="fw-bold">{{ $platform->employees_count }}</div>
                        <div style="font-size:0.7rem;color:var(--text-muted)">موظف</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:var(--bg-hover);border-radius:8px;padding:0.6rem 0.8rem;text-align:center">
                        <div class="fw-bold">{{ $platform->payroll_periods_count }}</div>
                        <div style="font-size:0.7rem;color:var(--text-muted)">دورة رواتب</div>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('platforms.show', $platform) }}" class="btn-walim" style="flex:1;justify-content:center">
                    <i class="bi bi-gear"></i> الإعدادات والضبط
                </a>
                <a href="{{ route('platforms.edit', $platform) }}" class="btn-ghost" style="padding:0.55rem 0.9rem">
                    <i class="bi bi-pencil"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="chart-card text-center py-5" style="color:var(--text-muted)">
            <i class="bi bi-shop" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>
            لا توجد منصات. <a href="{{ route('platforms.create') }}" style="color:var(--accent-light)">أضف أول منصة</a>
        </div>
    </div>
    @endforelse
</div>
@endsection
