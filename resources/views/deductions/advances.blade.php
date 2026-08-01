@extends('layouts.app')
@section('title','السلف النقدية')
@section('page-title','السلف النقدية')
@section('content')
<div class="row g-3">
    <div class="col-xl-4">
        <div class="chart-card fade-in">
            <div class="chart-title mb-3">➕ إضافة سلفة</div>
            <form method="POST" action="{{ route('deductions.advances.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label-dark">الموظف *</label>
                    <select name="employee_id" class="form-select form-select-dark" required>
                        <option value="">اختر الموظف</option>
                        @foreach($employees as $e)
                        <option value="{{ $e->id }}">{{ $e->name_ar ?: $e->name_en }} ({{ $e->iqama_number }})</option>
                        @endforeach
                    </select></div>
                <div class="mb-3"><label class="form-label-dark">الشهر *</label>
                    <input type="month" name="payroll_month" class="form-control form-control-dark" value="{{ date('Y-m') }}" required></div>
                <div class="mb-3"><label class="form-label-dark">المبلغ (ر.س) *</label>
                    <input type="number" name="amount" class="form-control form-control-dark" min="1" step="0.01" required></div>
                <div class="mb-4"><label class="form-label-dark">ملاحظات</label>
                    <input type="text" name="notes" class="form-control form-control-dark"></div>
                <button type="submit" class="btn-walim w-100"><i class="bi bi-plus-lg"></i> إضافة</button>
            </form>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="chart-card fade-in">
            <div class="chart-header"><div class="chart-title">سجل السلف</div></div>
            <div class="table-responsive">
                <table class="table walim-table">
                    <thead><tr><th>الموظف</th><th>الإقامة</th><th>الشهر</th><th>المبلغ</th><th>ملاحظات</th></tr></thead>
                    <tbody>
                        @forelse($advances as $a)
                        <tr>
                            <td>{{ $a->employee?->name_ar ?: $a->employee?->name_en }}</td>
                            <td><code style="color:var(--accent-light)">{{ $a->employee?->iqama_number }}</code></td>
                            <td>{{ \Carbon\Carbon::parse($a->payroll_month)->format('Y/m') }}</td>
                            <td class="fw-bold" style="color:var(--danger)">{{ number_format($a->amount, 0) }} ر.س</td>
                            <td class="text-muted">{{ $a->notes ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">لا سجلات</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $advances->links() }}</div>
        </div>
    </div>
</div>
@endsection
