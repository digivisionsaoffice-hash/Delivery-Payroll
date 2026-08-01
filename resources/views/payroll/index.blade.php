@extends('layouts.app')
@section('title', 'مسير الرواتب')
@section('page-title', 'مسير الرواتب')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <div class="chart-title">إدارة دورات الرواتب</div>
        <div class="chart-subtitle">احتساب وتصدير مسيرات الرواتب الشهرية</div>
    </div>
    <a href="{{ route('payroll.create') }}" class="btn-walim">
        <i class="bi bi-plus-lg"></i> دورة رواتب جديدة
    </a>
</div>

<div class="chart-card fade-in">
    <div class="table-responsive">
        <table class="table walim-table">
            <thead>
                <tr>
                    <th>المنصة</th>
                    <th>الشهر</th>
                    <th>عدد الموظفين</th>
                    <th>الحالة</th>
                    <th>المعتمد بواسطة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $period)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:32px;height:32px;background:var(--gradient-1);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0">
                                {{ mb_substr($period->platform->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $period->platform->name }}</div>
                                <small class="text-muted">{{ $period->platform->name_en }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="fw-bold">{{ \Carbon\Carbon::parse($period->month)->locale('ar')->translatedFormat('F Y') }}</td>
                    <td>
                        <span class="fw-bold" style="color:var(--accent-light)">{{ $period->entries_count }}</span>
                        <small class="text-muted"> موظف</small>
                    </td>
                    <td>
                        <span class="status-badge {{ match($period->status) {
                            'draft'      => 'badge-neutral',
                            'calculated' => 'badge-done',
                            'approved'   => 'badge-active',
                            'paid'       => 'badge-profit',
                            default      => 'badge-neutral'
                        } }}">
                            {{ match($period->status) {
                                'draft'      => '📝 مسودة',
                                'calculated' => '🔢 محسوب',
                                'approved'   => '✅ معتمد',
                                'paid'       => '💰 مصروف',
                                default      => $period->status
                            } }}
                        </span>
                    </td>
                    <td>
                        @if($period->approver)
                        <div style="font-size:0.8rem">{{ $period->approver->name }}</div>
                        <small class="text-muted">{{ $period->approved_at?->format('Y/m/d') }}</small>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('payroll.show', $period) }}" class="icon-btn" title="عرض" style="width:30px;height:30px">
                                <i class="bi bi-eye" style="font-size:0.8rem"></i>
                            </a>
                            @if(in_array($period->status, ['draft','calculated']))
                            <form method="POST" action="{{ route('payroll.calculate', $period) }}">
                                @csrf
                                <button type="submit" class="icon-btn" title="احتساب" style="width:30px;height:30px;color:var(--accent-light)">
                                    <i class="bi bi-cpu" style="font-size:0.8rem"></i>
                                </button>
                            </form>
                            @endif
                            @if($period->status === 'calculated')
                            <form method="POST" action="{{ route('payroll.approve', $period) }}">
                                @csrf
                                <button type="submit" class="icon-btn" title="اعتماد" style="width:30px;height:30px;color:var(--success)">
                                    <i class="bi bi-check-circle" style="font-size:0.8rem"></i>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('payroll.export', $period) }}" class="icon-btn" title="تصدير PDF" style="width:30px;height:30px;color:var(--warning)">
                                <i class="bi bi-file-earmark-pdf" style="font-size:0.8rem"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color:var(--text-muted)">
                        <i class="bi bi-calculator" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>
                        لا توجد دورات رواتب. <a href="{{ route('payroll.create') }}" style="color:var(--accent-light)">أنشئ أول دورة</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $periods->links() }}</div>
</div>
@endsection
