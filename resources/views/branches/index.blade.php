@extends('layouts.app')
@section('title','الفروع')
@section('page-title','إدارة الفروع')
@section('content')
<div class="d-flex justify-content-between mb-4 fade-in">
    <div class="chart-title">قائمة الفروع</div>
    <div class="d-flex gap-2">
        <form action="{{ route('branches.destroy_all') }}" method="POST" onsubmit="return confirm('⚠️ هل أنت متأكد من حذف جميع الفروع؟');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger" style="border-radius: 8px; padding: 0.5rem 1rem;">
                <i class="bi bi-trash"></i> حذف الجميع
            </button>
        </form>
        <a href="{{ route('branches.create') }}" class="btn-walim"><i class="bi bi-plus-lg"></i> فرع جديد</a>
    </div>
</div>
<div class="chart-card fade-in">
    <div class="table-responsive">
        <table class="table walim-table">
            <thead><tr><th>الفرع</th><th>الموظفون</th><th>الحالة</th><th>إجراءات</th></tr></thead>
            <tbody>
                @forelse($branches as $b)
                <tr>
                    <td class="fw-bold">{{ $b->name }}</td>
                    <td><span class="fw-bold" style="color:var(--accent-light)">{{ $b->employees_count }}</span></td>
                    <td><span class="status-badge {{ $b->is_active ? 'badge-active' : 'badge-loss' }}">{{ $b->is_active ? 'نشط' : 'غير نشط' }}</span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('branches.edit', $b) }}" class="icon-btn" style="width:28px;height:28px"><i class="bi bi-pencil" style="font-size:0.8rem"></i></a>
                            <form action="{{ route('branches.destroy', $b) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الفرع؟');">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-btn text-danger" style="width:28px;height:28px;background:rgba(239,68,68,0.1)">
                                    <i class="bi bi-trash" style="font-size:0.8rem"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">لا توجد فروع</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
