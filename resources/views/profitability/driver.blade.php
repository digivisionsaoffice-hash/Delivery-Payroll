@extends('layouts.app')
@section('title','تقرير الموظف')
@section('page-title','تقرير ربحية الموظف')
@section('content')
<div class="row g-3">
    <div class="col-xl-4">
        <div class="chart-card fade-in text-center">
            <div style="width:64px;height:64px;background:var(--gradient-1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 1rem">🧑‍💼</div>
            <h5 class="fw-bold">{{ $employee->name_ar ?: $employee->name_en }}</h5>
            <code style="color:var(--accent-light)">{{ $employee->iqama_number }}</code>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="chart-card fade-in">
            <div class="chart-title mb-3">📈 تاريخ الربحية</div>
            <div class="table-responsive">
                <table class="table walim-table">
                    <thead><tr><th>الشهر</th><th>المنصة</th><th>الطلبات</th><th>الإيراد</th><th>الراتب</th><th>الربح / خسارة</th></tr></thead>
                    <tbody>
                        @forelse($entries as $e)
                        <tr>
                            <td>{{ $e->payrollPeriod?->month?->format('Y/m') }}</td>
                            <td>{{ $e->payrollPeriod?->platform?->name }}</td>
                            <td>{{ number_format($e->total_orders) }}</td>
                            <td>{{ number_format($e->total_revenue, 0) }}</td>
                            <td>{{ number_format($e->net_salary, 0) }}</td>
                            <td style="font-weight:700;color:{{ $e->profit_loss >= 0 ? 'var(--success)' : 'var(--danger)' }}">{{ number_format($e->profit_loss, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted">لا توجد بيانات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
