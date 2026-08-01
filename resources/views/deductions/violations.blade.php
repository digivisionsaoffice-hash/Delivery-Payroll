@extends('layouts.app')
@section('title','المخالفات المرورية')
@section('page-title','المخالفات المرورية')
@section('content')
<div class="chart-card fade-in">
    <div class="chart-header">
        <div class="chart-title">سجل المخالفات</div>
        <small class="text-muted">استورد المخالفات عبر صفحة الاستيراد</small>
    </div>
    <div class="table-responsive">
        <table class="table walim-table">
            <thead><tr><th>الموظف</th><th>الشهر</th><th>نوع المخالفة</th><th>التاريخ</th><th>المبلغ</th><th>رقم اللوحة</th></tr></thead>
            <tbody>
                @forelse($violations as $v)
                <tr>
                    <td>{{ $v->employee?->name_ar ?: $v->employee?->name_en }}</td>
                    <td>{{ \Carbon\Carbon::parse($v->payroll_month)->format('Y/m') }}</td>
                    <td>{{ $v->violation_type ?? '—' }}</td>
                    <td>{{ $v->violation_date?->format('Y/m/d') ?? '—' }}</td>
                    <td class="fw-bold" style="color:var(--danger)">{{ number_format($v->amount, 0) }} ر.س</td>
                    <td>{{ $v->plate_number ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">لا سجلات مخالفات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $violations->links() }}</div>
</div>
@endsection
